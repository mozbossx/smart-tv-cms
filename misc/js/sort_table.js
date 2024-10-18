function sortTable(columnIndex) {
    var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
    table = document.getElementById("usersTable");
    switching = true;
    dir = "asc";
    while (switching) {
        switching = false;
        rows = table.rows;
        for (i = 1; i < (rows.length - 1); i++) {
            shouldSwitch = false;
            x = rows[i].getElementsByTagName("TD")[columnIndex];
            y = rows[i + 1].getElementsByTagName("TD")[columnIndex];
            
            if (dir == "asc") {
                if (compareValues(x, y) > 0) {
                    shouldSwitch = true;
                    break;
                }
            } else if (dir == "desc") {
                if (compareValues(x, y) < 0) {
                    shouldSwitch = true;
                    break;
                }
            }
        }
        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            switchcount++;
        } else {
            if (switchcount == 0 && dir == "asc") {
                dir = "desc";
                switching = true;
            }
        }
    }
}

function compareValues(x, y) {
    const xValue = x.textContent || x.innerText;
    const yValue = y.textContent || y.innerText;

    // Check if the values are dates
    const xDate = parseDate(xValue);
    const yDate = parseDate(yValue);
    if (xDate && yDate) {
        return xDate - yDate;
    }

    // Check if the values are numbers
    const xNum = parseFloat(xValue);
    const yNum = parseFloat(yValue);
    if (!isNaN(xNum) && !isNaN(yNum)) {
        return xNum - yNum;
    }

    // Default to string comparison
    return xValue.localeCompare(yValue);
}

function parseDate(dateString) {
    // Assuming date format is MM/DD/YYYY | HH:MM AM/PM
    const parts = dateString.split('|');
    if (parts.length === 2) {
        const datePart = parts[0].trim();
        const timePart = parts[1].trim();
        const [month, day, year] = datePart.split('/');
        let [time, ampm] = timePart.split(' ');
        let [hours, minutes] = time.split(':');
        
        if (ampm.toUpperCase() === 'PM' && hours !== '12') {
            hours = parseInt(hours) + 12;
        }
        if (ampm.toUpperCase() === 'AM' && hours === '12') {
            hours = '00';
        }
        
        return new Date(year, month - 1, day, hours, minutes);
    }
    return null;
}