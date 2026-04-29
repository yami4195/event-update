(function () {
  const USERS_KEY = "huUsers";
  const CURRENT_USER_KEY = "huCurrentUser";
  const EVENTS_KEY = "huEvents";
  const REGISTERED_KEY = "huRegisteredEvents";
  const CATEGORIES = [
    "Seminar / Academic Talk",
    "Training",
    "Club Programs",
    "Sport Events",
    "Cultural Events",
    "Hackathons"
  ];
  const DEFAULT_IMAGE = "https://images.unsplash.com/photo-1523580494863-6f3031224c94?auto=format&fit=crop&w=900&q=80";
  const now = Date.now();
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const initialEvents = [
    { id: "seed-1", title: "AI and Research Seminar", date: "2026-05-15", time: "10:00", location: "Main Campus Hall A", category: CATEGORIES[0], imageUrl: "https://images.unsplash.com/photo-1475721027785-f74eccf877e2?auto=format&fit=crop&w=900&q=80", description: "A seminar on AI applications in Ethiopian higher education.", organizerEmail: "organizer@hu.edu.et", registrations: [] },
    { id: "seed-2", title: "Frontend Skills Training", date: "2026-05-18", time: "14:00", location: "ICT Lab 2", category: CATEGORIES[1], imageUrl: "https://images.unsplash.com/photo-1515879218367-8466d910aaa4?auto=format&fit=crop&w=900&q=80", description: "Practical training on modern web frontend tools.", organizerEmail: "organizer@hu.edu.et", registrations: [] },
    { id: "seed-3", title: "Engineering Club Meetup", date: "2026-05-21", time: "16:30", location: "Engineering Block", category: CATEGORIES[2], imageUrl: "https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=900&q=80", description: "Club members discuss projects and innovation ideas.", organizerEmail: "organizer@hu.edu.et", registrations: [] },
    { id: "seed-4", title: "Inter-College Football", date: "2026-05-25", time: "15:00", location: "HU Sports Field", category: CATEGORIES[3], imageUrl: "https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=900&q=80", description: "Friendly football event among colleges.", organizerEmail: "organizer@hu.edu.et", registrations: [] },
    { id: "seed-5", title: "Traditional Culture Night", date: "2026-05-29", time: "18:00", location: "Student Center", category: CATEGORIES[4], imageUrl: "https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=900&q=80", description: "Celebrate cultural performances and food.", organizerEmail: "organizer@hu.edu.et", registrations: [] },
    { id: "seed-6", title: "HU Innovation Hackathon", date: "2026-06-03", time: "09:00", location: "Innovation Hub", category: CATEGORIES[5], imageUrl: "https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=900&q=80", description: "48-hour challenge to build impactful student solutions.", organizerEmail: "organizer@hu.edu.et", registrations: [] },
    { id: "seed-7", title: "Academic Advising Talk", date: "2026-04-10", time: "11:00", location: "Library Auditorium", category: CATEGORIES[0], imageUrl: "https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=900&q=80", description: "Guidance for course planning and success.", organizerEmail: "organizer@hu.edu.et", registrations: [] },
    { id: "seed-8", title: "Leadership Training Bootcamp", date: "2026-04-03", time: "13:00", location: "Conference Room B", category: CATEGORIES[1], imageUrl: "https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=900&q=80", description: "Interactive leadership and team sessions.", organizerEmail: "organizer@hu.edu.et", registrations: [] }
  ];

  function getUsers() {
    return JSON.parse(localStorage.getItem(USERS_KEY) || "[]");
  }

  function getCurrentUser() {
    return JSON.parse(localStorage.getItem(CURRENT_USER_KEY) || "null");
  }

  function getEvents() {
    return JSON.parse(localStorage.getItem(EVENTS_KEY) || "[]");
  }

  function saveEvents(events) {
    localStorage.setItem(EVENTS_KEY, JSON.stringify(events));
  }

  function getRegisteredMap() {
    return JSON.parse(localStorage.getItem(REGISTERED_KEY) || "{}");
  }

  function saveRegisteredMap(map) {
    localStorage.setItem(REGISTERED_KEY, JSON.stringify(map));
  }

  function setCurrentUser(user) {
    localStorage.setItem(CURRENT_USER_KEY, JSON.stringify(user));
  }

  function protectRoute() {
    const page = document.body.dataset.page;
    const user = getCurrentUser();
    if (["events", "customer-dashboard", "organizer-dashboard"].includes(page) && !user) {
      window.location.href = "login.html";
      return false;
    }
    if (page === "customer-dashboard" && user && user.role !== "Student") {
      window.location.href = "organizer-dashboard.html";
      return false;
    }
    if (page === "organizer-dashboard" && user && user.role !== "Organizer") {
      window.location.href = "customer-dashboard.html";
      return false;
    }
    return true;
  }

  function seedEvents() {
    if (!getEvents().length) {
      saveEvents(initialEvents);
    }
  }

  function isUpcoming(eventItem) {
    return new Date(eventItem.date + "T" + (eventItem.time || "00:00")).getTime() >= now;
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
      localStorage.removeItem(CURRENT_USER_KEY);
      window.location.href = "login.html";
    });
  }

  function eventCard(eventItem, withAction) {
    const actionButton = withAction ? '<button class="btn btn-primary event-action" data-event-id="' + eventItem.id + '" type="button">Register</button>' : "";
    return '<article class="event-card"><img src="' + (eventItem.imageUrl || DEFAULT_IMAGE) + '" alt="' + eventItem.title + '"><div class="event-card-content"><h3>' + eventItem.title + '</h3><p class="meta"><strong>Date:</strong> ' + eventItem.date + " " + eventItem.time + '</p><p class="meta"><strong>Category:</strong> ' + eventItem.category + '</p><p class="meta"><strong>Location:</strong> ' + eventItem.location + "</p>" + actionButton + "</div></article>";
  }

  function renderHome() {
    const container = document.getElementById("featuredEventsGrid");
    if (!container) return;
    const events = getEvents().filter(isUpcoming).slice(0, 4);
    container.innerHTML = events.map(function (item) { return eventCard(item, false); }).join("");
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
      const all = getEvents();
      const filtered = all.filter(function (eventItem) {
        const categoryOk = categoryFilter.value === "all" || eventItem.category === categoryFilter.value;
        const dateOk = dateFilter.value === "all" || (dateFilter.value === "upcoming" ? isUpcoming(eventItem) : !isUpcoming(eventItem));
        return categoryOk && dateOk;
      });
      grid.innerHTML = filtered.slice(0, limit).map(function (item) { return eventCard(item, true); }).join("");
      showMoreBtn.style.display = filtered.length > limit ? "inline-flex" : "none";
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
      const registerBtn = event.target.closest(".event-action");
      if (!registerBtn) return;
      const user = getCurrentUser();
      if (!user) {
        window.location.href = "login.html";
        return;
      }
      const eventId = registerBtn.dataset.eventId;
      const registeredMap = getRegisteredMap();
      const userEvents = registeredMap[user.email] || [];
      if (!userEvents.includes(eventId)) {
        userEvents.push(eventId);
      }
      registeredMap[user.email] = userEvents;
      saveRegisteredMap(registeredMap);

      const allEvents = getEvents();
      const targetEvent = allEvents.find(function (item) { return item.id === eventId; });
      if (targetEvent && !targetEvent.registrations.some(function (r) { return r.email === user.email; })) {
        targetEvent.registrations.push({
          name: user.name,
          email: user.email,
          registrationDate: new Date().toISOString().split("T")[0]
        });
      }
      saveEvents(allEvents);
      window.alert("Registration successful.");
    });

    draw();
  }

  function setupSignup() {
    const form = document.getElementById("signupForm");
    if (!form) return;
    const message = document.getElementById("signupMessage");
    form.addEventListener("submit", function (event) {
      event.preventDefault();
      message.textContent = "";
      const name = document.getElementById("signupName").value.trim();
      const email = document.getElementById("signupEmail").value.trim().toLowerCase();
      const password = document.getElementById("signupPassword").value.trim();
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
      if (!role) {
        message.textContent = "Please select your role";
        return;
      }
      const users = getUsers();
      if (users.some(function (u) { return u.email === email; })) {
        message.textContent = "Email already exists.";
        return;
      }
      const user = { name: name, email: email, password: password, role: role };
      users.push(user);
      localStorage.setItem(USERS_KEY, JSON.stringify(users));
      setCurrentUser(user);
      window.location.href = "events.html";
    });
  }

  function setupLogin() {
    const form = document.getElementById("loginForm");
    if (!form) return;
    const message = document.getElementById("loginMessage");
    form.addEventListener("submit", function (event) {
      event.preventDefault();
      message.textContent = "";
      const email = document.getElementById("loginEmail").value.trim().toLowerCase();
      const password = document.getElementById("loginPassword").value.trim();
      if (!emailRegex.test(email)) {
        message.textContent = "Invalid email";
        return;
      }
      const user = getUsers().find(function (u) {
        return u.email === email;
      });
      if (!user) {
        message.textContent = "Invalid email";
        return;
      }
      if (user.password !== password) {
        message.textContent = "Incorrect password";
        return;
      }
      setCurrentUser(user);
      window.location.href = "events.html";
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
      const allEvents = getEvents();
      const registeredIds = getRegisteredMap()[user.email] || [];
      const registered = allEvents.filter(function (item) { return registeredIds.includes(item.id); });
      const available = allEvents.filter(function (item) { return isUpcoming(item) && !registeredIds.includes(item.id); });
      availableGrid.innerHTML = available.slice(0, availableLimit).map(function (item) { return eventCard(item, false); }).join("");
      registeredGrid.innerHTML = registered.slice(0, registeredLimit).map(function (item) { return eventCard(item, false); }).join("");
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
    const statsPast = document.getElementById("statPastEvents");
    const moreBtn = document.getElementById("seeMoreOrganizer");
    const formMessage = document.getElementById("formMessage");
    const editId = document.getElementById("editEventId");
    const cancelEditBtn = document.getElementById("cancelEditBtn");
    if (!form || !list || !regs || !statsTotal || !statsReg || !statsUpcoming || !statsPast || !moreBtn || !formMessage || !editId || !cancelEditBtn) return;

    let limit = 4;
    cancelEditBtn.style.display = "none";

    function byOwner(events) {
      return events.filter(function (item) { return item.organizerEmail === user.email; });
    }

    function updateStats() {
      const events = byOwner(getEvents());
      const registrations = events.reduce(function (sum, item) { return sum + (item.registrations || []).length; }, 0);
      statsTotal.textContent = String(events.length);
      statsReg.textContent = String(registrations);
      statsUpcoming.textContent = String(events.filter(isUpcoming).length);
      statsPast.textContent = String(events.filter(function (item) { return !isUpcoming(item); }).length);
    }

    function draw() {
      const ownEvents = byOwner(getEvents());
      list.innerHTML = ownEvents.slice(0, limit).map(function (item) {
        return '<article class="event-card"><img src="' + (item.imageUrl || DEFAULT_IMAGE) + '" alt="' + item.title + '"><div class="event-card-content"><h3>' + item.title + '</h3><p class="meta"><strong>Date:</strong> ' + item.date + " " + item.time + '</p><p class="meta"><strong>Category:</strong> ' + item.category + '</p><p class="meta"><strong>Location:</strong> ' + item.location + '</p><div class="form-actions"><button class="btn btn-secondary btn-small" type="button" data-action="view" data-id="' + item.id + '">View registrations</button><button class="btn btn-primary btn-small" type="button" data-action="edit" data-id="' + item.id + '">Edit</button><button class="btn btn-danger btn-small" type="button" data-action="delete" data-id="' + item.id + '">Delete</button></div></div></article>';
      }).join("");
      moreBtn.style.display = ownEvents.length > limit ? "inline-flex" : "none";
      updateStats();
    }

    function clearForm() {
      form.reset();
      editId.value = "";
      formMessage.textContent = "";
      cancelEditBtn.style.display = "none";
    }

    form.addEventListener("submit", function (event) {
      event.preventDefault();
      const title = document.getElementById("eventTitle").value.trim();
      const description = document.getElementById("eventDescription").value.trim();
      const date = document.getElementById("eventDate").value;
      const time = document.getElementById("eventTime").value;
      const location = document.getElementById("eventLocation").value.trim();
      const category = document.getElementById("eventCategory").value;
      const imageUrl = document.getElementById("eventImageUrl").value.trim();
      if (!title || !description || !date || !time || !location || !category) {
        formMessage.textContent = "Please fill all required fields.";
        return;
      }
      const allEvents = getEvents();
      if (editId.value) {
        const current = allEvents.find(function (item) { return item.id === editId.value; });
        if (current) {
          current.title = title;
          current.description = description;
          current.date = date;
          current.time = time;
          current.location = location;
          current.category = category;
          current.imageUrl = imageUrl;
        }
      } else {
        allEvents.push({
          id: "ev-" + Date.now(),
          title: title,
          description: description,
          date: date,
          time: time,
          location: location,
          category: category,
          imageUrl: imageUrl,
          organizerEmail: user.email,
          registrations: []
        });
      }
      saveEvents(allEvents);
      clearForm();
      draw();
    });

    list.addEventListener("click", function (event) {
      const button = event.target.closest("button[data-action]");
      if (!button) return;
      const action = button.dataset.action;
      const id = button.dataset.id;
      const allEvents = getEvents();
      const target = allEvents.find(function (item) { return item.id === id; });
      if (!target) return;
      if (action === "view") {
        const entries = (target.registrations || []).map(function (r) {
          return "<li>" + r.name + " (" + r.email + ") - " + r.registrationDate + "</li>";
        }).join("");
        regs.innerHTML = entries ? "<ul>" + entries + "</ul>" : "<p class='helper-text'>No registrations yet.</p>";
      }
      if (action === "edit") {
        editId.value = target.id;
        document.getElementById("eventTitle").value = target.title;
        document.getElementById("eventDescription").value = target.description;
        document.getElementById("eventDate").value = target.date;
        document.getElementById("eventTime").value = target.time;
        document.getElementById("eventLocation").value = target.location;
        document.getElementById("eventCategory").value = target.category;
        document.getElementById("eventImageUrl").value = target.imageUrl || "";
        cancelEditBtn.style.display = "inline-flex";
      }
      if (action === "delete") {
        if (!window.confirm("Delete this event?")) return;
        saveEvents(allEvents.filter(function (item) { return item.id !== id; }));
        draw();
      }
    });

    cancelEditBtn.addEventListener("click", clearForm);
    moreBtn.addEventListener("click", function () {
      limit += 4;
      draw();
    });

    draw();
  }

  seedEvents();
  if (!protectRoute()) return;
  setupNavbar();
  setupLogin();
  setupSignup();
  renderHome();
  renderEventsPage();
  setupCustomerDashboard();
  setupOrganizerDashboard();
})();
