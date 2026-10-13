(() => {
  "use strict";
  const config = window.AIRA_CONFIG || {};
  const apiBase = (config.apiBase || "").replace(/\/$/, "");
  const form = document.getElementById("assessment-form");
  const file = document.getElementById("cv");
  const fileHelp = document.getElementById("file-help");
  const status = document.getElementById("form-status");
  const maxBytes = 10 * 1024 * 1024;
  const allowed = new Set(["application/pdf", "application/vnd.openxmlformats-officedocument.wordprocessingml.document"]);

  const say = (message, kind = "") => {
    if (!status) return;
    status.textContent = message;
    status.className = "form-status" + (kind ? " " + kind : "");
  };

  if (file && fileHelp) {
    file.addEventListener("change", () => {
      const selected = file.files && file.files[0];
      if (!selected) {
        fileHelp.textContent = "Choose a PDF or DOCX file from your device.";
        return;
      }
      fileHelp.textContent = selected.name + " · " + (selected.size / (1024 * 1024)).toFixed(2) + " MB";
      if (selected.size > maxBytes) say("That file is larger than 10 MB. Choose a smaller CV.", "error");
      else if (selected.type && !allowed.has(selected.type)) say("Use a PDF or DOCX file.", "error");
      else say("File selected. It will be uploaded only after you submit.", "");
    });
  }

  if (!form) return;
  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    const selected = file && file.files && file.files[0];
    if (!selected) return say("Choose your CV file first.", "error");
    if (selected.size > maxBytes) return say("That file is larger than 10 MB. Choose a smaller CV.", "error");
    const ext = selected.name.split(".").pop().toLowerCase();
    if (!["pdf", "docx"].includes(ext)) return say("Use a PDF or DOCX file.", "error");
    const jobDescription = form.elements.job_description.value.trim();
    if (jobDescription.length < 80) return say("Add a more complete job description (at least 80 characters) so the feedback can be role-specific.", "error");

    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    button.textContent = "Submitting securely…";
    say("Submitting your request. Please keep this page open.", "");

    const body = new FormData();
    body.append("email", form.elements.email.value.trim());
    body.append("cv", selected);
    body.append("job_title", form.elements.job_title.value.trim());
    body.append("job_description", jobDescription);
    body.append("consent", form.elements.consent.checked ? "1" : "0");

    try {
      const endpoint = (apiBase || window.location.origin) + "/api/v1/assessments";
      const response = await fetch(endpoint, {
        method: "POST",
        body,
        headers: { "Accept": "application/json" },
        credentials: "omit"
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok) {
        const message = payload.message || (response.status === 404 || response.status === 405
          ? "The assessment service is not deployed yet. Your file may not have been accepted; please try again later."
          : "We couldn't submit this assessment. Check the information and try again.");
        throw new Error(message);
      }
      const workflowId = payload.data && (payload.data.id || payload.data.workflow_id);
      if (!workflowId) throw new Error("The service response was incomplete. Contact support before submitting again.");
      form.reset();
      if (fileHelp) fileHelp.textContent = "Choose a PDF or DOCX file from your device.";
      say("Assessment request received. Reference: " + workflowId + ". Status and delivery support will be available once account notifications are enabled.", "success");
    } catch (error) {
      say(error instanceof Error ? error.message : "Network error. Please try again.", "error");
    } finally {
      button.disabled = false;
      button.innerHTML = 'Submit assessment <span aria-hidden="true">→</span>';
    }
  });
})();
