(function () {
  const calendarGrid = document.getElementById("calendarGrid");
  const monthLabel = document.getElementById("calendarMonthLabel");
  const prevBtn = document.getElementById("calendarPrev");
  const nextBtn = document.getElementById("calendarNext");

  if (!calendarGrid || !monthLabel || !prevBtn || !nextBtn) return;

  const now = new Date();
  let month = now.getMonth();
  let year = now.getFullYear();

  // Demo event days for homepage presentation.
  const eventDays = [3, 9, 14, 18, 24, 29];

  function renderCalendar() {
    const first = new Date(year, month, 1);
    const startDay = first.getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();

    monthLabel.textContent = first.toLocaleDateString("en-US", {
      month: "long",
      year: "numeric"
    });

    const cells = [];

    for (let i = 0; i < startDay; i += 1) {
      const d = daysInPrevMonth - startDay + i + 1;
      cells.push('<div class="calendar-cell muted">' + d + "</div>");
    }

    for (let d = 1; d <= daysInMonth; d += 1) {
      const hasEvent = eventDays.indexOf(d) !== -1;
      const cls = hasEvent ? "calendar-cell has-event" : "calendar-cell";
      cells.push('<div class="' + cls + '">' + d + "</div>");
    }

    while (cells.length % 7 !== 0) {
      cells.push('<div class="calendar-cell muted">' + (cells.length % 7 + 1) + "</div>");
    }

    calendarGrid.innerHTML = cells.join("");
  }

  prevBtn.addEventListener("click", function () {
    month -= 1;
    if (month < 0) {
      month = 11;
      year -= 1;
    }
    renderCalendar();
  });

  nextBtn.addEventListener("click", function () {
    month += 1;
    if (month > 11) {
      month = 0;
      year += 1;
    }
    renderCalendar();
  });

  renderCalendar();
})();
