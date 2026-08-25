document.addEventListener("DOMContentLoaded", function () {

    const deleteButtons = document.querySelectorAll(".delete-btn");

    deleteButtons.forEach(function (button) {

        button.addEventListener("click", function (event) {

            const confirmed = confirm(
                "Are you sure you want to delete this record?"
            );

            if (!confirmed) {
                event.preventDefault();
            }

        });

    });


    const paymentForm = document.querySelector("#paymentForm");

    if (paymentForm) {

        paymentForm.addEventListener("submit", function (event) {

            const amountInput = document.querySelector("#amount");

            if (amountInput) {

                const amount = parseFloat(amountInput.value);

                if (isNaN(amount) || amount <= 0) {

                    event.preventDefault();

                    alert(
                        "Please enter a valid payment amount greater than 0."
                    );

                    amountInput.focus();

                }

            }

        });

    }


    const searchInput = document.querySelector("#studentSearch");
    const studentTable = document.querySelector("#studentTable");

    if (searchInput && studentTable) {

        const rows = studentTable.querySelectorAll("tbody tr");

        searchInput.addEventListener("keyup", function () {

            const searchText = searchInput.value.toLowerCase().trim();

            rows.forEach(function (row) {

                const rowText = row.textContent.toLowerCase();

                if (rowText.includes(searchText)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }

            });

        });

    }

});