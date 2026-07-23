document.addEventListener("DOMContentLoaded", function () {
  setupSplitToggle();
  setupExpenseForm();
  setupMemberForm();
  setupSettlementButtons();
  setupInviteResponses();
  loadCategoryChart();
});

// Show/hide percentage or custom inputs depending on the chosen split type
function setupSplitToggle() {
  const radios = document.querySelectorAll('input[name="split_type"]');
  if (!radios.length) return;

  radios.forEach(radio => {
    radio.addEventListener("change", function () {
      const type = this.value;
      document.querySelectorAll(".percent-input").forEach(el => el.classList.add("hidden"));
      document.querySelectorAll(".custom-input").forEach(el => el.classList.add("hidden"));
      document.querySelectorAll(".equal-only").forEach(el => el.style.display = "none");

      const hint = document.getElementById("splitHint");

      if (type === "equal") {
        document.querySelectorAll(".equal-only").forEach(el => el.style.display = "inline-flex");
        hint.textContent = "All included members split the amount equally.";
      } else if (type === "percentage") {
        document.querySelectorAll(".percent-input").forEach(el => el.classList.remove("hidden"));
        hint.textContent = "Enter each member's share as a percentage. Must total 100%.";
      } else if (type === "custom") {
        document.querySelectorAll(".custom-input").forEach(el => el.classList.remove("hidden"));
        hint.textContent = "Enter each member's exact amount. Must total the full expense amount.";
      }
    });
  });
}

// Submit the add-expense form via AJAX (fetch) so the page never reloads
function setupExpenseForm() {
  const form = document.getElementById("addExpenseForm");
  if (!form) return;

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    const msg = document.getElementById("expenseMsg");
    msg.textContent = "Adding...";
    msg.className = "msg";

    fetch("ajax_add_expense.php", {
      method: "POST",
      body: new FormData(form)
    })
      .then(res => res.json())
      .then(data => {
        msg.textContent = data.message;
        msg.className = "msg " + (data.success ? "success" : "error");
        if (data.success) {
          setTimeout(() => window.location.reload(), 700);
        }
      })
      .catch(() => {
        msg.textContent = "Network error. Please try again.";
        msg.className = "msg error";
      });
  });
}

function setupMemberForm() {
  const form = document.getElementById("addMemberForm");
  if (!form) return;

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    const msg = document.getElementById("memberMsg");
    msg.textContent = "Adding...";
    msg.className = "msg";

    fetch("add_member.php", {
      method: "POST",
      body: new FormData(form)
    })
      .then(res => res.json())
      .then(data => {
        msg.textContent = data.message;
        msg.className = "msg " + (data.success ? "success" : "error");
        if (data.success) {
          setTimeout(() => window.location.reload(), 700);
        }
      });
  });
}

function setupSettlementButtons() {
  document.querySelectorAll(".mark-settled").forEach(btn => {
    btn.addEventListener("click", function () {
      const body = new FormData();
      body.append("group_id", this.dataset.group);
      body.append("from", this.dataset.from);
      body.append("to", this.dataset.to);
      body.append("amount", this.dataset.amount);

      fetch("settle_payment.php", { method: "POST", body })
        .then(res => res.json())
        .then(data => {
          if (data.success) window.location.reload();
        });
    });
  });
}

// Handles the Accept/Decline buttons on pending group invites (dashboard)
function setupInviteResponses() {
  console.log("setupInviteResponses() called - looking for invite buttons");
  const buttons = document.querySelectorAll(".respond-invite");
  console.log("Found " + buttons.length + " invite buttons");

  buttons.forEach(btn => {
    btn.addEventListener("click", function () {
      console.log("Invite button clicked!");
      console.log("dataset:", this.dataset);

      const msg = document.getElementById("inviteMsg");
      const inviteId = this.dataset.invite;
      const action = this.dataset.action;

      if (!inviteId || !action) {
        const errorMsg = "Error: Missing invite ID or action.";
        console.error(errorMsg);
        if (msg) {
          msg.textContent = errorMsg;
          msg.className = "msg error";
        }
        return;
      }

      // Disable button to prevent double-click
      this.disabled = true;
      this.textContent = "Processing...";

      const body = new FormData();
      body.append("invite_id", inviteId);
      body.append("action", action);

      console.log("Sending request to respond_invite.php with:", { inviteId, action });

      fetch("respond_invite.php", {
        method: "POST",
        body: body
      })
        .then(response => {
          console.log("Response status:", response.status);
          return response.json();
        })
        .then(data => {
          console.log("Response data:", data);
          if (msg) {
            msg.textContent = data.message;
            msg.className = "msg " + (data.success ? "success" : "error");
          }
          if (data.success) {
            setTimeout(() => window.location.reload(), 1000);
          } else {
            // Re-enable button on failure
            this.disabled = false;
            this.textContent = action === "accept" ? "Accept" : "Decline";
          }
        })
        .catch(error => {
          console.error("Fetch error:", error);
          if (msg) {
            msg.textContent = "Network error: " + error.message;
            msg.className = "msg error";
          }
          this.disabled = false;
          this.textContent = action === "accept" ? "Accept" : "Decline";
        });
    });
  });
}

// Fetches category totals from get_balances.php and draws a doughnut chart
function loadCategoryChart() {
  const canvas = document.getElementById("categoryChart");
  if (!canvas || typeof GROUP_ID === "undefined") return;

  fetch("get_balances.php?group_id=" + GROUP_ID)
    .then(res => res.json())
    .then(data => {
      if (data.length === 0) return;
      new Chart(canvas, {
        type: "doughnut",
        data: {
          labels: data.map(d => d.category),
          datasets: [{
            data: data.map(d => d.total),
            backgroundColor: ["#1B4332", "#D97706", "#40916C", "#B5451B", "#74C69D"]
          }]
        },
        options: { plugins: { legend: { position: "bottom" } } }
      });
    });
}