// Calendar functionality
class DatePicker {
    constructor(inputId, calendarId) {
        this.input = document.getElementById(inputId);
        this.calendar = document.getElementById(calendarId);
        this.currentDate = new Date();
        this.selectedDate = null;

        this.init();
    }

    init() {
        this.input.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleCalendar();
        });

        this.renderCalendar();

        document.addEventListener('click', (e) => {
            if (!this.calendar.contains(e.target) && e.target !== this.input) {
                this.calendar.classList.remove('active');
            }
        });
    }

    toggleCalendar() {
        const wasActive = this.calendar.classList.contains('active');
        document.querySelectorAll('.calendarDropdown').forEach(cal => {
            cal.classList.remove('active');
        });
        if (!wasActive) {
            this.calendar.classList.add('active');
        }
    }

    renderCalendar() {
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const prevLastDay = new Date(year, month, 0);

        const firstDayIndex = firstDay.getDay();
        const lastDayDate = lastDay.getDate();
        const prevLastDayDate = prevLastDay.getDate();

        const months = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];

        let calendarHTML = `
            <div class="calendarHeader">
                <button onclick="window.datePickers['${this.input.id}'].prevMonth()">◀</button>
                <div class="calendarMonth">${months[month]} ${year}</div>
                <button onclick="window.datePickers['${this.input.id}'].nextMonth()">▶</button>
            </div>
            <div class="calendarGrid">
                <div class="calendarDayHeader">Sun</div>
                <div class="calendarDayHeader">Mon</div>
                <div class="calendarDayHeader">Tue</div>
                <div class="calendarDayHeader">Wed</div>
                <div class="calendarDayHeader">Thu</div>
                <div class="calendarDayHeader">Fri</div>
                <div class="calendarDayHeader">Sat</div>
        `;

        // Previous month days
        for (let i = firstDayIndex; i > 0; i--) {
            calendarHTML += `<div class="calendarDay otherMonth">${prevLastDayDate - i + 1}</div>`;
        }

        // Current month days
        const today = new Date();
        for (let i = 1; i <= lastDayDate; i++) {
            const date = new Date(year, month, i);
            const isPast = date < new Date(today.getFullYear(), today.getMonth(), today.getDate());
            const isSelected = this.selectedDate &&
                date.getDate() === this.selectedDate.getDate() &&
                date.getMonth() === this.selectedDate.getMonth() &&
                date.getFullYear() === this.selectedDate.getFullYear();

            const classes = ['calendarDay'];
            if (isPast) classes.push('disabled');
            if (isSelected) classes.push('selected');

            calendarHTML += `<div class="${classes.join(' ')}" 
                                 onclick="window.datePickers['${this.input.id}'].selectDate(${year}, ${month}, ${i})"
                                 ${isPast ? '' : 'style="cursor: pointer;"'}>
                                 ${i}
                             </div>`;
        }

        // Next month days
        const remainingDays = 42 - (firstDayIndex + lastDayDate);
        for (let i = 1; i <= remainingDays; i++) {
            calendarHTML += `<div class="calendarDay otherMonth">${i}</div>`;
        }

        calendarHTML += '</div>';
        this.calendar.innerHTML = calendarHTML;
    }

    selectDate(year, month, day) {
        const date = new Date(year, month, day);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (date < today) return;

        this.selectedDate = date;

        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const formattedDate = `${String(day).padStart(2, '0')} ${months[month]} ${year}`;
        this.input.value = formattedDate;

        this.calendar.classList.remove('active');
        this.renderCalendar();
    }

    prevMonth() {
        this.currentDate.setMonth(this.currentDate.getMonth() - 1);
        this.renderCalendar();
    }

    nextMonth() {
        this.currentDate.setMonth(this.currentDate.getMonth() + 1);
        this.renderCalendar();
    }
}

// Initialize date pickers
window.datePickers = {};

document.addEventListener('DOMContentLoaded', function () {
    window.datePickers['pickupDate'] = new DatePicker('pickupDate', 'pickupCalendar');
    window.datePickers['returnDate'] = new DatePicker('returnDate', 'returnCalendar');
});

// Other functions
function clearLocation() {
    document.getElementById('location').value = '';
}

function showVehicles() {
    goToPage('page2');
}

function goToPage(pageId) {
    document.querySelectorAll('.page').forEach(page => {
        page.classList.remove('active');
    });
    document.getElementById(pageId).classList.add('active');
    window.scrollTo(0, 0);
}

function editSection(section) {
    console.log('Editing section:', section);
}

function selectVehicle(vehicleName) {
    console.log('Selected vehicle:', vehicleName);
}

function showFeatures(vehicleType) {
    console.log('Showing features for:', vehicleType);
    alert('Showing features and price details');
}