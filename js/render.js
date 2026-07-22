// Render Announcements and Events from API or Data Fallback
async function renderAnnouncements() {
    const container = document.querySelector('.announcement-box');
    if (!container) return;

    const announcements = typeof fetchAnnouncements === 'function' 
        ? await fetchAnnouncements() 
        : (typeof parishData !== 'undefined' ? parishData.announcements : []);

    let html = '<h2>Latest Announcements</h2>';
    if (announcements.length === 0) {
        html += '<p style="color: #666;">No announcements currently available.</p>';
    } else {
        announcements.forEach(item => {
            html += `
                <div class="announcement-item">
                    <div class="announcement-image">
                        <img src="${item.image || 'images/mamamarylindogon.jpg'}" alt="${item.title}" />
                    </div>
                    <div class="announcement-content">
                        <h3>${item.category || 'PARISH ANNOUNCEMENT'}</h3>
                        <h4>${item.title}</h4>
                        <p>${item.description}</p>
                        <a href="${item.link || '#'}" class="read-more">READ MORE →</a>
                    </div>
                </div>
            `;
        });
    }
    container.innerHTML = html;
}

async function renderEvents() {
    const container = document.querySelector('.events-box');
    if (!container) return;

    const eventsList = typeof fetchEvents === 'function' 
        ? await fetchEvents() 
        : (typeof parishData !== 'undefined' ? parishData.events : []);

    const highlights = eventsList.slice(0, 3);
    let html = '<h2>Upcoming Parish Highlights</h2>';

    if (highlights.length === 0) {
        html += '<p style="color: #666;">No upcoming events scheduled.</p>';
    } else {
        highlights.forEach(item => {
            html += `
                <div class="event-item">
                    <div class="event-label">${item.category || 'PARISH EVENT'}</div>
                    <div class="event-title">${item.title}</div>
                    <div class="event-date">
                        <div class="date-box">
                            <div class="month">${item.month || ''}</div>
                            <div class="day">${item.day || ''}</div>
                            <div class="dow">${item.dow || ''}</div>
                        </div>
                        <div>
                            <p style="margin: 0; color: #666; font-size: 0.9rem;">${item.description}</p>
                            <div class="event-details">
                                <span>⏰ ${item.time || 'TBA'}</span>
                                <span>${item.location || 'Parish Church'}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
    }

    html += '<a href="news.html" class="view-all">VIEW FULL CALENDAR →</a>';
    container.innerHTML = html;
}

// Render on page load
document.addEventListener('DOMContentLoaded', async () => {
    await renderAnnouncements();
    await renderEvents();
});
