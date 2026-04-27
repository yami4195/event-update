document.querySelectorAll(".view-details").forEach((button) => {
  button.addEventListener("click", () => {
    const eventTitle = button.closest(".event-card")?.querySelector("h3")?.textContent || "this event";
    alert(`More details for ${eventTitle} will be available soon.`);
  });
});
