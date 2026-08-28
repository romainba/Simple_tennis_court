
const AJAX_FMT = "JSON";

function initializeUsersTableSorting(container) {
    const table = container.querySelector("table.usersTable");
    if (!table) return;

    const numericColumns = new Set([0, 4, 5, 7]);
    const buttons = table.querySelectorAll(".usersTableSort");
    let activeColumn = 0;
    let direction = "ascending";

    const updateIndicators = () => {
        buttons.forEach((button) => {
            const isActive = Number(button.dataset.column) === activeColumn;
            button.dataset.direction = isActive ? direction : "";
            button.setAttribute("aria-sort", isActive ? direction : "none");
            button.querySelector(".usersTableSortUp").style.setProperty("color", isActive && direction === "ascending" ? "#000" : "#ddd", "important");
            button.querySelector(".usersTableSortDown").style.setProperty("color", isActive && direction === "descending" ? "#000" : "#ddd", "important");
        });
    };

    const sortRows = () => {
        const tbody = table.tBodies[0];
        if (!tbody) return;

        const rows = Array.from(tbody.rows).map((row, index) => ({ row, index }));
        rows.sort((left, right) => {
            const leftValue = left.row.cells[activeColumn]?.textContent.trim() || "";
            const rightValue = right.row.cells[activeColumn]?.textContent.trim() || "";
            const leftEmpty = leftValue === "";
            const rightEmpty = rightValue === "";

            if (leftEmpty || rightEmpty) {
                if (leftEmpty && rightEmpty) return left.index - right.index;
                return leftEmpty ? 1 : -1;
            }

            let result;
            if (numericColumns.has(activeColumn)) {
                result = Number(leftValue) - Number(rightValue);
            } else {
                result = leftValue.localeCompare(rightValue, undefined, {
                    numeric: true,
                    sensitivity: "base",
                });
            }

            if (result === 0) return left.index - right.index;
            return direction === "ascending" ? result : -result;
        });

        rows.forEach(({ row }) => tbody.appendChild(row));
    };

    buttons.forEach((button) => {
        const activate = () => {
            const column = Number(button.dataset.column);
            if (column === activeColumn) {
                direction = direction === "ascending" ? "descending" : "ascending";
            } else {
                activeColumn = column;
                direction = "ascending";
            }

            updateIndicators();
            sortRows();
        };

        button.addEventListener("click", activate);
        button.addEventListener("keydown", (event) => {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                activate();
            }
        });
    });

    updateIndicators();
}

function usersTable(elem, year) {
    var req = {
        'option' : 'com_ajax',
        'module' : 'court_usage',
        'format' : AJAX_FMT,
        'cmd'    : 'usersTable',
        'year'   : year
    };

    jQuery.ajax({
        type : 'POST',
        data: req,

        success: function(data) {
            resp = JSON.parse(data);
            var cell = document.getElementById(elem);
            cell.innerHTML = resp.data;
            initializeUsersTableSorting(cell);
        },
        error: function(response) {
            alert("internal error");
        }
    })
}

jQuery(document).ready(function() {
    usersTable('usersTable', document.getElementById('usersTableYear').value);
})
