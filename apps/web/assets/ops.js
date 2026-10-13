(() => {
  "use strict";

  const API_DEFAULT = "https://app.aireadyassessment.com";
  const ALLOWED_TOOLS = new Set([
    "app.health",
    "database.schema",
    "jobs.failed",
    "assessment.summary",
    "assessment.inspect"
  ]);
  const config = window.AIRA_CONFIG || {};
  const originInput = document.getElementById("api-origin");
  const tokenInput = document.getElementById("mcp-token");
  const connectForm = document.getElementById("connect-form");
  const connectButton = document.getElementById("connect-button");
  const disconnectButton = document.getElementById("disconnect-button");
  const refreshButton = document.getElementById("refresh-button");
  const statusEl = document.getElementById("connection-status");
  const toolList = document.getElementById("tool-list");

  let bearerToken = "";
  let sessionId = "";
  let protocolVersion = "2025-03-26";
  let endpoint = "";

  originInput.value = (config.apiBase || API_DEFAULT).replace(/\/$/, "");

  function status(message, kind = "") {
    statusEl.textContent = message;
    statusEl.dataset.kind = kind;
  }

  function setConnected(connected) {
    disconnectButton.disabled = !connected;
    refreshButton.disabled = !connected;
    connectButton.disabled = false;
    connectButton.textContent = connected ? "Reconnect" : "Connect & inspect";
  }

  function parseResponseBody(text, contentType) {
    if (!text.trim()) return null;
    if (contentType.includes("text/event-stream")) {
      const messages = [];
      let dataLines = [];
      for (const line of text.split(/\r?\n/)) {
        if (line.startsWith("data:")) {
          dataLines.push(line.slice(5).trimStart());
        } else if (line === "" && dataLines.length) {
          messages.push(dataLines.join("\n"));
          dataLines = [];
        }
      }
      if (dataLines.length) messages.push(dataLines.join("\n"));
      for (let i = messages.length - 1; i >= 0; i--) {
        try { return JSON.parse(messages[i]); } catch (_) { /* try prior SSE event */ }
      }
      throw new Error("The MCP server returned an unreadable event stream.");
    }
    try { return JSON.parse(text); }
    catch (_) { throw new Error("The MCP server returned a non-JSON response."); }
  }

  async function postMcp(message, includeVersion = true) {
    const headers = {
      "Accept": "application/json, text/event-stream",
      "Content-Type": "application/json",
      "Authorization": "Bearer " + bearerToken
    };
    if (includeVersion) headers["MCP-Protocol-Version"] = protocolVersion;
    if (sessionId) headers["Mcp-Session-Id"] = sessionId;

    const response = await fetch(endpoint, {
      method: "POST",
      mode: "cors",
      credentials: "omit",
      cache: "no-store",
      headers,
      body: JSON.stringify(message)
    });
    const returnedSession = response.headers.get("Mcp-Session-Id");
    if (returnedSession) sessionId = returnedSession;
    const bodyText = await response.text();
    const payload = parseResponseBody(bodyText, response.headers.get("content-type") || "");
    if (!response.ok) {
      const detail = payload && payload.error && payload.error.message
        ? payload.error.message
        : bodyText.slice(0, 300) || ("HTTP " + response.status);
      throw new Error("MCP request failed (" + response.status + "): " + detail);
    }
    if (payload && payload.error) throw new Error(payload.error.message || "MCP protocol error.");
    return payload;
  }

  async function initialize() {
    const result = await postMcp({
      jsonrpc: "2.0",
      id: 1,
      method: "initialize",
      params: {
        protocolVersion,
        capabilities: {},
        clientInfo: { name: "AIReadyAssessment Ops Console", version: "1.0.0" }
      }
    }, false);

    if (!result || !result.result || !result.result.protocolVersion) {
      throw new Error("MCP initialize returned an unexpected response.");
    }
    protocolVersion = result.result.protocolVersion;

    await postMcp({ jsonrpc: "2.0", method: "notifications/initialized" });
    return result.result;
  }

  async function loadTools() {
    const response = await postMcp({
      jsonrpc: "2.0",
      id: 2,
      method: "tools/list",
      params: {}
    });
    const tools = response && response.result && Array.isArray(response.result.tools)
      ? response.result.tools
      : [];
    const safeTools = tools.filter(tool => ALLOWED_TOOLS.has(tool.name));
    renderTools(safeTools);
    if (!safeTools.length) {
      status("Connected, but none of the expected read-only diagnostics tools were returned.", "error");
      return;
    }
    status("Connected. Loaded " + safeTools.length + " allowlisted read-only diagnostic tools.", "success");
  }

  function renderTools(tools) {
    toolList.replaceChildren();
    if (!tools.length) {
      const empty = document.createElement("p");
      empty.className = "ops-empty";
      empty.textContent = "No allowlisted diagnostics tools are available.";
      toolList.append(empty);
      return;
    }

    for (const tool of tools) {
      const card = document.createElement("article");
      card.className = "ops-tool-card";
      const heading = document.createElement("h3");
      heading.textContent = tool.name;
      const description = document.createElement("p");
      description.textContent = tool.description || "Read-only diagnostic tool.";
      const label = document.createElement("label");
      const argsId = "args-" + tool.name.replace(/[^a-z0-9-]/gi, "-");
      label.htmlFor = argsId;
      label.textContent = "Arguments (JSON)";
      const args = document.createElement("textarea");
      args.id = argsId;
      args.className = "ops-tool-args";
      args.spellcheck = false;
      args.value = tool.name === "jobs.failed" ? '{"limit":5}'
        : tool.name === "assessment.inspect" ? '{"id":""}'
        : tool.name === "database.schema" ? '{}'
        : '{}';
      const runButton = document.createElement("button");
      runButton.type = "button";
      runButton.className = "button button-primary";
      runButton.textContent = "Run diagnostic";
      const output = document.createElement("pre");
      output.className = "ops-tool-output";
      output.hidden = true;
      runButton.addEventListener("click", async () => {
        let parsedArgs;
        try {
          parsedArgs = JSON.parse(args.value || "{}");
          if (!parsedArgs || Array.isArray(parsedArgs) || typeof parsedArgs !== "object") {
            throw new Error("Arguments must be a JSON object.");
          }
        } catch (error) {
          output.hidden = false;
          output.textContent = error.message || "Invalid JSON arguments.";
          return;
        }
        if (!bearerToken) {
          status("Disconnected. Reconnect before running a diagnostic.", "error");
          return;
        }
        runButton.disabled = true;
        runButton.textContent = "Running…";
        output.hidden = false;
        output.textContent = "Requesting diagnostic…";
        try {
          const result = await postMcp({
            jsonrpc: "2.0",
            id: Date.now(),
            method: "tools/call",
            params: { name: tool.name, arguments: parsedArgs }
          });
          output.textContent = JSON.stringify(result.result ?? result, null, 2);
        } catch (error) {
          output.textContent = error instanceof Error ? error.message : "Diagnostic request failed.";
        } finally {
          runButton.disabled = false;
          runButton.textContent = "Run diagnostic";
        }
      });
      card.append(heading, description, label, args, runButton, output);
      toolList.append(card);
    }
  }

  connectForm.addEventListener("submit", async event => {
    event.preventDefault();
    const enteredToken = tokenInput.value.trim();
    const enteredOrigin = originInput.value.trim().replace(/\/$/, "");
    tokenInput.value = "";
    if (!enteredOrigin.startsWith("https://")) {
      status("Use the HTTPS API origin.", "error");
      return;
    }
    if (!enteredToken || enteredToken.length < 32) {
      status("Enter the configured MCP bearer token (at least 32 characters).", "error");
      return;
    }

    bearerToken = enteredToken;
    endpoint = enteredOrigin + "/mcp/diagnostics";
    sessionId = "";
    protocolVersion = "2025-03-26";
    connectButton.disabled = true;
    connectButton.textContent = "Connecting…";
    status("Authenticating and negotiating MCP protocol…");
    toolList.replaceChildren();

    try {
      await initialize();
      await loadTools();
      setConnected(true);
    } catch (error) {
      bearerToken = "";
      sessionId = "";
      setConnected(false);
      status((error instanceof Error ? error.message : "Connection failed.") +
        " Check MCP_ENABLED, the token, API CORS settings and Cloudflare Access policies.", "error");
      const empty = document.createElement("p");
      empty.className = "ops-empty";
      empty.textContent = "No diagnostic results loaded.";
      toolList.replaceChildren(empty);
    }
  });

  refreshButton.addEventListener("click", async () => {
    if (!bearerToken) return;
    refreshButton.disabled = true;
    try { await loadTools(); }
    catch (error) { status(error instanceof Error ? error.message : "Could not refresh tools.", "error"); }
    finally { refreshButton.disabled = !bearerToken; }
  });

  disconnectButton.addEventListener("click", () => {
    bearerToken = "";
    sessionId = "";
    endpoint = "";
    toolList.replaceChildren();
    const empty = document.createElement("p");
    empty.className = "ops-empty";
    empty.textContent = "Disconnected. Connect again to load diagnostics.";
    toolList.append(empty);
    setConnected(false);
    status("Disconnected. The token has been cleared from page memory.");
  });
})();
