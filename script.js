(function () {
  const API_AUTH = "api/auth";
  const API_EVENTS = "api/events";
  const API_REGISTRATIONS = "api/registrations";
  const API_ADMIN = "api/admin";
  const DEFAULT_IMAGE = "https://images.unsplash.com/photo-1523580494863-6f3031224c94?auto=format&fit=crop&w=900&q=80";
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  let sessionUser = null;
  let eventsCache = [];
  let registeredIds = [];
  let registeredEvents = [];

  function getCurrentUser() {
    return sessionUser;
  }

  function isOrganizer(user) {
    return user && user.role === "organizer";
  }

  function isStudent(user) {
    return user && user.role === "student";
  }

  function isAdmin(user) {
    return user && user.role === "admin";
  }

  function redirectForRole(user) {
    if (isAdmin(user)) {
      window.location.href = "admin-dashboard.html";
    } else if (isOrganizer(user)) {
      window.location.href = "organizer-dashboard.html";
    } else {
      window.location.href = "events.html";
    }
  }

  function statusBadge(status) {
    return '<span class="status-badge status-' + status + '">' + status + "</span>";
  }

  function capacityLine(eventItem) {
    if (eventItem.capacity == null) return "";
    const count = eventItem.registrationCount || 0;
    const full = eventItem.isFull ? " (Full)" : "";
    return '<p class="meta"><strong>Seats:</strong> ' + count + " / " + eventItem.capacity + full + "</p>";
  }

  function apiFetch(url, options) {
    return fetch(url, Object.assign({ credentials: "same-origin" }, options || {}))
      .then(function (res) {
        return res.json().then(function (data) {
          return { ok: res.ok, status: res.status, data: data };
        });
      });
  }

  function loadSession() {
    return apiFetch(API_AUTH + "/me.php").then(function (result) {
      if (result.ok && result.data.success) {
        sessionUser = result.data.user || null;
      } else {
        sessionUser = null;
      }
      return sessionUser;
    }).catch(function () {
      sessionUser = null;
      return null;
    });
  }

  function logout() {
    return apiFetch(API_AUTH + "/logout.php", { method: "POST" }).finally(function () {
      sessionUser = null;
      window.location.href = "login.html";
    });
  }

  function loadEvents(params) {
    const query = new URLSearchParams(params || { scope: "public" });
    return apiFetch(API_EVENTS + "/list.php?" + query.toString()).then(function (result) {
      if (result.ok && result.data.success) {
        eventsCache = result.data.events || [];
      }
      return eventsCache;
    }).catch(function () {
      eventsCache = [];
      return eventsCache;
    });
  }

  function loadRegistered() {
    return apiFetch(API_REGISTRATIONS + "/mine.php").then(function (result) {
      if (result.ok && result.data.success) {
        registeredIds = result.data.registeredIds || [];
        registeredEvents = result.data.registeredEvents || [];
      }
      return registeredIds;
    }).catch(function () {
      registeredIds = [];
      registeredEvents = [];
      return registeredIds;
    });
  }

  function getEvents() {
    return eventsCache;
  }

  function isRegistered(eventId) {
    return registeredIds.indexOf(Number(eventId)) !== -1;
  }

  function protectRoute() {
    const page = document.body.dataset.page;
    const user = getCurrentUser();
    if (["events", "customer-dashboard", "organizer-dashboard"].includes(page) && !user) {
      window.location.href = "login.html";
      return false;
    }
    if (page === "customer-dashboard" && user && !isStudent(user)) {
      window.location.href = "organizer-dashboard.html";
      return false;
    }
    if (page === "organizer-dashboard" && user && !isOrganizer(user)) {
      window.location.href = isAdmin(user) ? "admin-dashboard.html" : "customer-dashboard.html";
      return false;
    }
    if (page === "admin-dashboard" && user && !isAdmin(user)) {
      window.location.href = isOrganizer(user) ? "organizer-dashboard.html" : "customer-dashboard.html";
      return false;
    }
    return true;
  }

  function isUpcoming(eventItem) {
    const time = eventItem.time ? eventItem.time.slice(0, 5) : "00:00";
    return new Date(eventItem.date + "T" + time).getTime() >= Date.now();
  }

  function cardActionMode() {
    const page = document.body.dataset.page;
    const user = getCurrentUser();
    if (user && isStudent(user) && (page === "events" || page === "customer-dashboard" || page === "home")) {
      return "register";
    }
    if (!user && page === "home") {
      return "login";
    }
    return null;
  }

  function setupNavbar() {
    const navAuth = document.getElementById("navAuthArea");
    if (!navAuth) return;
    const user = getCurrentUser();
    if (!user) {
      navAuth.innerHTML = '<a class="btn btn-secondary" href="login.html">Login</a><a class="btn btn-primary" href="signup.html">Signup</a>';
      return;
    }
    navAuth.innerHTML = '<span class="user-chip" id="navUserName"></span><button class="btn btn-secondary" id="logoutBtn" type="button">Logout</button>';
    document.getElementById("navUserName").textContent = user.name;
    document.getElementById("logoutBtn").addEventListener("click", function () {
      logout();
    });
  }

  function eventCard(eventItem, actionMode) {
    let actionButton = "";
    if (actionMode === "register") {
      if (isRegistered(eventItem.id)) {
        actionButton = '<button class="btn btn-secondary" type="button" disabled>Registered</button>';
      } else if (eventItem.isFull) {
        actionButton = '<button class="btn btn-secondary" type="button" disabled>Event Full</button>';
      } else if (isUpcoming(eventItem)) {
        actionButton = '<button class="btn btn-primary event-action" data-event-id="' + eventItem.id + '" type="button">Register</button>';
      } else {
        actionButton = '<button class="btn btn-secondary" type="button" disabled>Event Ended</button>';
      }
    } else if (actionMode === "login" && isUpcoming(eventItem)) {
      actionButton = '<a class="btn btn-primary" href="login.html">Login to Register</a>';
    } else if (actionMode === "unregister") {
      if (isUpcoming(eventItem)) {
        actionButton = '<button class="btn btn-danger event-unregister" data-event-id="' + eventItem.id + '" type="button">Unregister</button>';
      } else {
        actionButton = '<button class="btn btn-secondary" type="button" disabled>Event Ended</button>';
      }
    }
    return '<article class="event-card"><img src="' + (eventItem.imageUrl || DEFAULT_IMAGE) + '" alt="' + eventItem.title + '"><div class="event-card-content"><h3>' + eventItem.title + '</h3><p class="meta"><strong>Date:</strong> ' + eventItem.date + " " + eventItem.time + '</p><p class="meta"><strong>Category:</strong> ' + eventItem.category + '</p><p class="meta"><strong>Location:</strong> ' + eventItem.location + "</p>" + capacityLine(eventItem) + actionButton + "</div></article>";
  }

  function registerForEvent(eventId, onDone) {
    return apiFetch(API_REGISTRATIONS + "/register.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ eventId: eventId })
    }).then(function (result) {
      if (!result.ok || !result.data.success) {
        window.alert(result.data.message || "Registration failed.");
        return;
      }
      return loadRegistered().then(function () {
        if (onDone) onDone();
        window.alert("Registration successful.");
      });
    });
  }

  function unregisterFromEvent(eventId, onDone) {
    return apiFetch(API_REGISTRATIONS + "/unregister.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ eventId: eventId })
    }).then(function (result) {
      if (!result.ok || !result.data.success) {
        window.alert(result.data.message || "Could not unregister.");
        return;
      }
      return loadRegistered().then(function () {
        if (onDone) onDone();
        window.alert("Registration cancelled.");
      });
    });
  }

  function renderHome() {
    const container = document.getElementById("featuredEventsGrid");
    if (!container) return;
    const events = getEvents().filter(isUpcoming).slice(0, 4);
    container.innerHTML = events.map(function (item) { return eventCard(item, cardActionMode()); }).join("");
    const seeMoreBtn = document.getElementById("seeMoreHome");
    if (!seeMoreBtn) return;
    seeMoreBtn.addEventListener("click", function () {
      if (!getCurrentUser()) {
        window.location.href = "login.html";
        return;
      }
      window.location.href = "events.html";
    });
  }

  function renderEventsPage() {
    const grid = document.getElementById("eventsGrid");
    const categoryFilter = document.getElementById("categoryFilter");
    const dateFilter = document.getElementById("dateFilter");
    const showMoreBtn = document.getElementById("seeMoreEvents");
    if (!grid || !categoryFilter || !dateFilter || !showMoreBtn) return;

    let limit = 4;

    function draw() {
      const params = {
        scope: "public",
        category: categoryFilter.value,
        when: dateFilter.value
      };
      return loadEvents(params).then(function (all) {
        grid.innerHTML = all.slice(0, limit).map(function (item) { return eventCard(item, cardActionMode() || "register"); }).join("");
        showMoreBtn.style.display = all.length > limit ? "inline-flex" : "none";
      });
    }

    categoryFilter.addEventListener("change", function () {
      limit = 4;
      draw();
    });
    dateFilter.addEventListener("change", function () {
      limit = 4;
      draw();
    });
    showMoreBtn.addEventListener("click", function () {
      limit += 4;
      draw();
    });

    grid.addEventListener("click", function (event) {
      const registerBtn = event.target.closest(".event-action[data-event-id]");
      if (!registerBtn) return;
      const user = getCurrentUser();
      if (!user) {
        window.location.href = "login.html";
        return;
      }
      if (!isStudent(user)) {
        window.alert("Only students can register for events.");
        return;
      }
      const eventId = Number(registerBtn.dataset.eventId);
      registerBtn.disabled = true;
      registerForEvent(eventId, draw).catch(function () {
        window.alert("Could not reach the server.");
      }).finally(function () {
        registerBtn.disabled = false;
      });
    });

    draw();
  }

  function setupSignup() {
    const form = document.getElementById("signupForm");
    if (!form) return;
    const message = document.getElementById("signupMessage");
    const submitBtn = form.querySelector('button[type="submit"]');
    form.addEventListener("submit", function (event) {
      event.preventDefault();
      message.textContent = "";
      const name = document.getElementById("signupName").value.trim();
      const email = document.getElementById("signupEmail").value.trim().toLowerCase();
      const password = document.getElementById("signupPassword").value;
      const role = document.getElementById("signupRole").value;
      if (!name) {
        message.textContent = "Please enter your name";
        return;
      }
      if (!email) {
        message.textContent = "Please enter your email";
        return;
      }
      if (!emailRegex.test(email)) {
        message.textContent = "Invalid email";
        return;
      }
      if (!password) {
        message.textContent = "Please enter your password";
        return;
      }
      if (password.length < 6) {
        message.textContent = "Password must be at least 6 characters.";
        return;
      }
      if (!role) {
        message.textContent = "Please select your role";
        return;
      }
      if (submitBtn) submitBtn.disabled = true;
      apiFetch(API_AUTH + "/register.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name: name, email: email, password: password, role: role })
      }).then(function (result) {
        if (submitBtn) submitBtn.disabled = false;
        if (!result.ok || !result.data.success) {
          message.textContent = result.data.message || "Signup failed. Try again.";
          return;
        }
        if (result.data.pendingApproval) {
          message.textContent = result.data.message;
          return;
        }
        sessionUser = result.data.user;
        redirectForRole(sessionUser);
      }).catch(function () {
        if (submitBtn) submitBtn.disabled = false;
        message.textContent = "Could not reach the server. Is XAMPP running?";
      });
    });
  }

  function setupLogin() {
    const form = document.getElementById("loginForm");
    if (!form) return;
    const message = document.getElementById("loginMessage");
    const submitBtn = form.querySelector('button[type="submit"]');
    form.addEventListener("submit", function (event) {
      event.preventDefault();
      message.textContent = "";
      const email = document.getElementById("loginEmail").value.trim().toLowerCase();
      const password = document.getElementById("loginPassword").value;
      if (!emailRegex.test(email)) {
        message.textContent = "Invalid email";
        return;
      }
      if (!password) {
        message.textContent = "Please enter your password";
        return;
      }
      if (submitBtn) submitBtn.disabled = true;
      apiFetch(API_AUTH + "/login.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email: email, password: password })
      }).then(function (result) {
        if (submitBtn) submitBtn.disabled = false;
        if (!result.ok || !result.data.success) {
          message.textContent = result.data.message || "Login failed. Try again.";
          return;
        }
        sessionUser = result.data.user;
        redirectForRole(sessionUser);
      }).catch(function () {
        if (submitBtn) submitBtn.disabled = false;
        message.textContent = "Could not reach the server. Is XAMPP running?";
      });
    });
  }

  function setupCustomerDashboard() {
    const availableGrid = document.getElementById("availableEventsGrid");
    const registeredGrid = document.getElementById("registeredEventsGrid");
    const availableMoreBtn = document.getElementById("seeMoreAvailable");
    const registeredMoreBtn = document.getElementById("seeMoreRegistered");
    if (!availableGrid || !registeredGrid || !availableMoreBtn || !registeredMoreBtn) return;
    const user = getCurrentUser();
    if (!user) return;

    let availableLimit = 4;
    let registeredLimit = 4;

    function draw() {
      const available = getEvents().filter(function (item) {
        return isUpcoming(item) && !isRegistered(item.id);
      });
      const registered = registeredEvents;
      availableGrid.innerHTML = available.slice(0, availableLimit).map(function (item) { return eventCard(item, "register"); }).join("");
      registeredGrid.innerHTML = registered.slice(0, registeredLimit).map(function (item) { return eventCard(item, "unregister"); }).join("");
      availableMoreBtn.style.display = available.length > availableLimit ? "inline-flex" : "none";
      registeredMoreBtn.style.display = registered.length > registeredLimit ? "inline-flex" : "none";
    }

    availableMoreBtn.addEventListener("click", function () {
      availableLimit += 4;
      draw();
    });
    registeredMoreBtn.addEventListener("click", function () {
      registeredLimit += 4;
      draw();
    });

    function handleGridClick(event) {
      const registerBtn = event.target.closest(".event-action[data-event-id]");
      const unregisterBtn = event.target.closest(".event-unregister[data-event-id]");
      if (registerBtn) {
        registerBtn.disabled = true;
        registerForEvent(Number(registerBtn.dataset.eventId), function () {
          loadEvents({ scope: "public", when: "all" }).then(draw);
        }).finally(function () { registerBtn.disabled = false; });
        return;
      }
      if (unregisterBtn) {
        if (!window.confirm("Cancel your registration for this event?")) return;
        unregisterBtn.disabled = true;
        unregisterFromEvent(Number(unregisterBtn.dataset.eventId), function () {
          loadEvents({ scope: "public", when: "all" }).then(draw);
        }).finally(function () { unregisterBtn.disabled = false; });
      }
    }

    availableGrid.addEventListener("click", handleGridClick);
    registeredGrid.addEventListener("click", handleGridClick);

    draw();
  }

  function setupOrganizerDashboard() {
    const user = getCurrentUser();
    if (!user) return;

    const form = document.getElementById("eventForm");
    const list = document.getElementById("organizerEventsGrid");
    const regs = document.getElementById("registrationsList");
    const statsTotal = document.getElementById("statTotalEvents");
    const statsReg = document.getElementById("statTotalRegistrations");
    const statsUpcoming = document.getElementById("statUpcomingEvents");
    const formMessage = document.getElementById("formMessage");
    const editId = document.getElementById("editEventId");
    const cancelEditBtn = document.getElementById("cancelEditBtn");

    if (!form || !list) return;

    const dateInput = document.getElementById("eventDate");
    if (dateInput) {
      const today = new Date();
      const pad = function (n) { return String(n).padStart(2, "0"); };
      dateInput.min = today.getFullYear() + "-" + pad(today.getMonth() + 1) + "-" + pad(today.getDate());
    }

    const navUserName = document.getElementById("navUserName");
    if (navUserName) navUserName.textContent = user.name;

    const sidebarLogoutBtn = document.getElementById("sidebarLogoutBtn");
    if (sidebarLogoutBtn) {
      sidebarLogoutBtn.addEventListener("click", function () {
        logout();
      });
    }

    const adminSidebar = document.getElementById("adminSidebar");
    const mobileMenuBtn = document.getElementById("mobileMenuBtn");
    const closeSidebarBtn = document.getElementById("closeSidebarBtn");

    if (mobileMenuBtn && adminSidebar) {
      mobileMenuBtn.addEventListener("click", function () {
        adminSidebar.classList.add("open");
      });
    }
    if (closeSidebarBtn && adminSidebar) {
      closeSidebarBtn.addEventListener("click", function () {
        adminSidebar.classList.remove("open");
      });
    }

    const sidebarLinks = document.querySelectorAll(".sidebar-link");
    const tabPanes = document.querySelectorAll(".tab-pane");
    const topbarTitle = document.getElementById("topbarTitle");

    function switchTab(tabId, titleText) {
      tabPanes.forEach(function (pane) { pane.classList.remove("active"); });
      sidebarLinks.forEach(function (link) {
        link.classList.remove("active");
        if (link.dataset.tab === tabId) link.classList.add("active");
      });
      const targetPane = document.getElementById(tabId);
      if (targetPane) targetPane.classList.add("active");
      if (topbarTitle && titleText) topbarTitle.textContent = titleText;
      if (adminSidebar) adminSidebar.classList.remove("open");
    }

    sidebarLinks.forEach(function (link) {
      link.addEventListener("click", function (e) {
        e.preventDefault();
        const tabId = this.dataset.tab;
        const rawText = this.textContent.trim();
        const titleText = rawText.replace(/^[\u2700-\u27BF\u1F000-\u1F9FF\u2B50\u200D\u2600-\u26FF\s]+/, "");
        switchTab(tabId, titleText || "Dashboard");
      });
    });

    if (cancelEditBtn) cancelEditBtn.style.display = "none";

    function updateStats() {
      const events = getEvents();
      const registrations = events.reduce(function (sum, item) {
        return sum + (item.registrationCount || 0);
      }, 0);
      if (statsTotal) statsTotal.textContent = String(events.length);
      if (statsReg) statsReg.textContent = String(registrations);
      if (statsUpcoming) statsUpcoming.textContent = String(events.filter(isUpcoming).length);
    }

    function draw() {
      const ownEvents = getEvents();
      list.innerHTML = ownEvents.map(function (item) {
        return '<article class="event-card"><img src="' + (item.imageUrl || DEFAULT_IMAGE) + '" alt="' + item.title + '"><div class="event-card-content"><h3>' + item.title + " " + statusBadge(item.status) + '</h3><p class="meta"><strong>Date:</strong> ' + item.date + " " + item.time + '</p><p class="meta"><strong>Category:</strong> ' + item.category + '</p><p class="meta"><strong>Location:</strong> ' + item.location + "</p>" + capacityLine(item) + '<p class="meta"><strong>Registrations:</strong> ' + (item.registrationCount || 0) + '</p><div class="form-actions"><button class="btn btn-secondary btn-small" type="button" data-action="view" data-id="' + item.id + '">View registrations</button><button class="btn btn-primary btn-small" type="button" data-action="edit" data-id="' + item.id + '">Edit</button><button class="btn btn-danger btn-small" type="button" data-action="delete" data-id="' + item.id + '">Delete</button></div></div></article>';
      }).join("");
      updateStats();
    }

    function clearForm() {
      form.reset();
      editId.value = "";
      if (formMessage) formMessage.textContent = "";
      if (cancelEditBtn) cancelEditBtn.style.display = "none";
      const fTitle = document.getElementById("formTitle");
      if (fTitle) fTitle.textContent = "Create Event";
    }

    form.addEventListener("submit", function (event) {
      event.preventDefault();
      const fd = new FormData();
      fd.append("title", document.getElementById("eventTitle").value.trim());
      fd.append("description", document.getElementById("eventDescription").value.trim());
      fd.append("date", document.getElementById("eventDate").value);
      fd.append("time", document.getElementById("eventTime").value);
      fd.append("location", document.getElementById("eventLocation").value.trim());
      fd.append("category", document.getElementById("eventCategory").value);
      fd.append("status", document.getElementById("eventStatus").value);
      const capacityVal = document.getElementById("eventCapacity").value;
      if (capacityVal) fd.append("capacity", capacityVal);
      const imageUrl = document.getElementById("eventImageUrl").value.trim();
      if (imageUrl) fd.append("imageUrl", imageUrl);
      const imageFile = document.getElementById("eventImageFile").files[0];
      if (imageFile) fd.append("image", imageFile);

      if (!fd.get("title") || !fd.get("description") || !fd.get("date") || !fd.get("time") || !fd.get("location") || !fd.get("category")) {
        if (formMessage) formMessage.textContent = "Please fill all required fields.";
        return;
      }

      const isEdit = !!editId.value;
      const url = isEdit ? API_EVENTS + "/update.php" : API_EVENTS + "/create.php";
      if (isEdit) {
        fd.append("id", editId.value);
        if (!imageFile && !imageUrl) fd.append("keepImage", "1");
      }

      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) submitBtn.disabled = true;

      apiFetch(url, {
        method: "POST",
        body: fd
      }).then(function (result) {
        if (submitBtn) submitBtn.disabled = false;
        if (!result.ok || !result.data.success) {
          if (formMessage) formMessage.textContent = result.data.message || "Could not save event.";
          return;
        }
        if (formMessage) {
          formMessage.textContent = result.data.message;
          formMessage.style.color = "var(--primary-dark)";
        }
        clearForm();
        return loadEvents({ scope: "mine" }).then(draw);
      }).then(function () {
        setTimeout(function () { if (formMessage) formMessage.textContent = ""; }, 3000);
      }).catch(function () {
        if (submitBtn) submitBtn.disabled = false;
        if (formMessage) formMessage.textContent = "Could not reach the server.";
      });
    });

    list.addEventListener("click", function (event) {
      const button = event.target.closest("button[data-action]");
      if (!button) return;
      const action = button.dataset.action;
      const id = Number(button.dataset.id);
      const target = getEvents().find(function (item) { return Number(item.id) === id; });
      if (!target) return;

      if (action === "view") {
        apiFetch(API_EVENTS + "/registrations.php?event_id=" + id).then(function (result) {
          if (!result.ok || !result.data.success) {
            if (regs) regs.innerHTML = "<p class='helper-text'>" + (result.data.message || "Could not load registrations.") + "</p>";
            return;
          }
          const entries = (result.data.registrations || []).map(function (r) {
            return "<li>" + r.name + " (" + r.email + ") - " + r.registrationDate + "</li>";
          }).join("");
          if (regs) regs.innerHTML = entries ? "<ul style='margin-left:1.5rem;'>" + entries + "</ul>" : "<p class='helper-text'>No registrations yet.</p>";
          const regTitle = document.getElementById("registrationEventTitle");
          if (regTitle) regTitle.textContent = "for " + (result.data.eventTitle || target.title);
          switchTab("section-registrations", "Registrations");
        });
      }

      if (action === "edit") {
        editId.value = String(target.id);
        document.getElementById("eventTitle").value = target.title;
        document.getElementById("eventDescription").value = target.description;
        document.getElementById("eventDate").value = target.date;
        document.getElementById("eventTime").value = target.time;
        document.getElementById("eventLocation").value = target.location;
        document.getElementById("eventCategory").value = target.category;
        document.getElementById("eventImageUrl").value = target.imageUrl || "";
        document.getElementById("eventCapacity").value = target.capacity != null ? target.capacity : "";
        document.getElementById("eventStatus").value = target.status || "published";
        document.getElementById("eventImageFile").value = "";
        if (cancelEditBtn) cancelEditBtn.style.display = "inline-flex";
        const fTitle = document.getElementById("formTitle");
        if (fTitle) fTitle.textContent = "Edit Event";
        switchTab("section-create-event", "Edit Event");
      }

      if (action === "delete") {
        if (!window.confirm("Delete this event?")) return;
        apiFetch(API_EVENTS + "/delete.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: id })
        }).then(function (result) {
          if (!result.ok || !result.data.success) {
            window.alert(result.data.message || "Could not delete event.");
            return;
          }
          return loadEvents({ scope: "mine" }).then(draw);
        });
      }
    });

    if (cancelEditBtn) cancelEditBtn.addEventListener("click", clearForm);

    draw();
  }

  function setupAdminDashboard() {
    const user = getCurrentUser();
    if (!user || !isAdmin(user)) return;

    const navUserName = document.getElementById("navUserName");
    if (navUserName) navUserName.textContent = user.name;

    const sidebarLogoutBtn = document.getElementById("sidebarLogoutBtn");
    if (sidebarLogoutBtn) sidebarLogoutBtn.addEventListener("click", logout);

    const adminSidebar = document.getElementById("adminSidebar");
    const mobileMenuBtn = document.getElementById("mobileMenuBtn");
    const closeSidebarBtn = document.getElementById("closeSidebarBtn");
    if (mobileMenuBtn && adminSidebar) {
      mobileMenuBtn.addEventListener("click", function () { adminSidebar.classList.add("open"); });
    }
    if (closeSidebarBtn && adminSidebar) {
      closeSidebarBtn.addEventListener("click", function () { adminSidebar.classList.remove("open"); });
    }

    const sidebarLinks = document.querySelectorAll(".sidebar-link");
    const tabPanes = document.querySelectorAll(".tab-pane");
    const topbarTitle = document.getElementById("topbarTitle");

    function switchTab(tabId, titleText) {
      tabPanes.forEach(function (pane) { pane.classList.remove("active"); });
      sidebarLinks.forEach(function (link) {
        link.classList.remove("active");
        if (link.dataset.tab === tabId) link.classList.add("active");
      });
      const targetPane = document.getElementById(tabId);
      if (targetPane) targetPane.classList.add("active");
      if (topbarTitle && titleText) topbarTitle.textContent = titleText;
      if (adminSidebar) adminSidebar.classList.remove("open");
    }

    sidebarLinks.forEach(function (link) {
      link.addEventListener("click", function (e) {
        e.preventDefault();
        switchTab(this.dataset.tab, this.textContent.trim());
      });
    });

    function loadStats() {
      return apiFetch(API_ADMIN + "/stats.php").then(function (result) {
        if (!result.ok || !result.data.success) return;
        const s = result.data.stats;
        document.getElementById("statUsers").textContent = s.users;
        document.getElementById("statPending").textContent = s.pendingUsers;
        document.getElementById("statPublished").textContent = s.publishedEvents;
        document.getElementById("statRegistrations").textContent = s.registrations;
      });
    }

    function loadUsers() {
      return apiFetch(API_ADMIN + "/users.php").then(function (result) {
        const body = document.getElementById("adminUsersBody");
        if (!body) return;
        if (!result.ok || !result.data.success) {
          body.innerHTML = '<tr><td colspan="5">Could not load users.</td></tr>';
          return;
        }
        body.innerHTML = result.data.users.map(function (u) {
          if (u.role === "admin") {
            return "<tr><td>" + u.name + "</td><td>" + u.email + "</td><td>" + u.role + "</td><td>" + statusBadge(u.status) + "</td><td>—</td></tr>";
          }
          const options = ["active", "pending", "suspended"].map(function (st) {
            return '<option value="' + st + '"' + (st === u.status ? " selected" : "") + ">" + st + "</option>";
          }).join("");
          return '<tr><td>' + u.name + '</td><td>' + u.email + '</td><td>' + u.role + '</td><td>' + statusBadge(u.status) + '</td><td><select class="admin-user-status" data-user-id="' + u.id + '">' + options + '</select></td></tr>';
        }).join("");
      });
    }

    function loadAdminEvents() {
      return apiFetch(API_ADMIN + "/events.php").then(function (result) {
        const list = document.getElementById("adminEventsList");
        if (!list) return;
        if (!result.ok || !result.data.success) {
          list.innerHTML = "<p class='helper-text'>Could not load events.</p>";
          return;
        }
        list.innerHTML = result.data.events.map(function (item) {
          return '<article class="event-card"><img src="' + (item.imageUrl || DEFAULT_IMAGE) + '" alt="' + item.title + '"><div class="event-card-content"><h3>' + item.title + " " + statusBadge(item.status) + '</h3><p class="meta"><strong>Organizer:</strong> ' + item.organizerName + '</p><p class="meta"><strong>Date:</strong> ' + item.date + " " + item.time + "</p>" + capacityLine(item) + '<div class="admin-event-actions"><select class="admin-event-status" data-event-id="' + item.id + '"><option value="published"' + (item.status === "published" ? " selected" : "") + '>Published</option><option value="draft"' + (item.status === "draft" ? " selected" : "") + '>Draft</option><option value="cancelled"' + (item.status === "cancelled" ? " selected" : "") + ">Cancelled</option></select></div></div></article>";
        }).join("");
      });
    }

    document.getElementById("adminUsersBody").addEventListener("change", function (e) {
      const select = e.target.closest(".admin-user-status");
      if (!select) return;
      apiFetch(API_ADMIN + "/users.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ userId: Number(select.dataset.userId), status: select.value })
      }).then(function (result) {
        if (!result.ok || !result.data.success) {
          window.alert(result.data.message || "Update failed.");
          return;
        }
        loadUsers();
        loadStats();
      });
    });

    document.getElementById("adminEventsList").addEventListener("change", function (e) {
      const select = e.target.closest(".admin-event-status");
      if (!select) return;
      apiFetch(API_ADMIN + "/events.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ eventId: Number(select.dataset.eventId), status: select.value })
      }).then(function (result) {
        if (!result.ok || !result.data.success) {
          window.alert(result.data.message || "Update failed.");
          return;
        }
        loadAdminEvents();
        loadStats();
      });
    });

    Promise.all([loadStats(), loadUsers(), loadAdminEvents()]);
  }

  function boot() {
    if (!protectRoute()) return;
    setupNavbar();
    setupLogin();
    setupSignup();

    const page = document.body.dataset.page;
    const loads = [];

    if (page === "organizer-dashboard") {
      loads.push(loadEvents({ scope: "mine" }));
    } else if (page === "admin-dashboard") {
      loads.push(Promise.resolve());
    } else if (document.getElementById("featuredEventsGrid") || document.getElementById("eventsGrid") || page === "customer-dashboard") {
      loads.push(loadEvents({ scope: "public", when: "all" }));
    }

    if (getCurrentUser() && isStudent(getCurrentUser()) && (page === "events" || page === "customer-dashboard")) {
      loads.push(loadRegistered());
    }

    return Promise.all(loads).then(function () {
      renderHome();
      renderEventsPage();
      setupCustomerDashboard();
      setupOrganizerDashboard();
      setupAdminDashboard();
    });
  }

  loadSession().then(boot);
})();

