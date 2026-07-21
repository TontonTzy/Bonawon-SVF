// Render Announcements and Events from Data
function renderAnnouncements() {
    const container = document.querySelector('.announcement-box');
    if (!container) return;

    let html = '<h2>Latest Announcements</h2>';
    parishData.announcements.forEach(item => {
        html += `
            <div class="announcement-item">
                <div class="announcement-image">
                    <img src="${item.image}" alt="${item.title}" />
                </div>
                <div class="announcement-content">
                    <h3>${item.category}</h3>
                    <h4>${item.title}</h4>
                    <p>${item.description}</p>
                    <a href="${item.link}" class="read-more">READ MORE →</a>
                </div>
            </div>
        `;
    });
    container.innerHTML = html;
}

function renderEvents() {
    const container = document.querySelector('.events-box');
    if (!container) return;

    let html = '<h2>Upcoming Parish Events</h2>';
    parishData.events.forEach(item => {
        html += `
            <div class="event-item">
                <div class="event-label">${item.category}</div>
                <div class="event-title">${item.title}</div>
                <div class="event-date">
                    <div class="date-box">
                        <div class="month">${item.month}</div>
                        <div class="day">${item.day}</div>
                        <div class="dow">${item.dow}</div>
                    </div>
                    <div>
                        <p style="margin: 0; color: #666; font-size: 0.9rem;">${item.description}</p>
                        <div class="event-details">
                            <span>⏰ ${item.time}</span>
                            <span>📍 ${item.location}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    html += '<a href="#" class="view-all">VIEW ALL EVENTS →</a>';
    container.innerHTML = html;
}

// Render on page load
document.addEventListener('DOMContentLoaded', () => {
    renderAnnouncements();
    renderEvents();
});
