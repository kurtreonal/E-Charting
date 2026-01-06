
    //wait for the document to be fully loaded before running the script
    document.addEventListener('DOMContentLoaded', function() {

    //select all radio buttons with the 'toggle-radio' class.
    const toggleRadios = document.querySelectorAll('.toggle-radio');

    toggleRadios.forEach(radio => {
        // Store the checked state
        radio.addEventListener('change', function() {
            const clickedRadio = this;
            const groupName = clickedRadio.name;

            // Clear the wasChecked flag for all radios in this group
            toggleRadios.forEach(r => {
                if (r.name === groupName) {
                    r.dataset.wasChecked = 'false';
                }
            });

            // Mark the current radio as wasChecked
            if (clickedRadio.checked) {
                clickedRadio.dataset.wasChecked = 'true';
            }
        });

        // Handle click to toggle/uncheck
        radio.addEventListener('click', function() {
            const clickedRadio = this;

            // If already checked, uncheck it
            if (clickedRadio.checked && clickedRadio.dataset.wasChecked === 'true') {
                clickedRadio.checked = false;
                clickedRadio.dataset.wasChecked = 'false';
            } else if (!clickedRadio.checked) {
                // If not checked, check it and mark as wasChecked
                clickedRadio.checked = true;
                clickedRadio.dataset.wasChecked = 'true';

                // Clear wasChecked for other radios in the same group
                toggleRadios.forEach(otherRadio => {
                    if (otherRadio !== clickedRadio && otherRadio.name === clickedRadio.name) {
                        otherRadio.dataset.wasChecked = 'false';
                    }
                });
            }
        });
    });
});

