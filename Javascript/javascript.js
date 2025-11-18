//humburger function
document.addEventListener('DOMContentLoaded', () => {
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');

    hamburger.addEventListener('click', () => {
        //toggle hamburger active class
        hamburger.classList.toggle('active');
        navLinks.classList.toggle('active');
    });

    //close menu when clicking a nav item
    document.querySelectorAll('.nav-item').forEach(n => n.addEventListener('click', () => {
        hamburger.classList.remove('active');
        navLinks.classList.remove('active');
    }));
});

//load DOM content
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('add-patient-form');

    if (form) {
        //Load saved form data from localStorage on page load
        loadFormData();

        //Save form data to localStorage as user types the data
        form.querySelectorAll('input, textarea, select').forEach(function(field) {
            field.addEventListener('change', function() {
                saveFormData();
            });
            field.addEventListener('blur', function() {
                saveFormData();
            });
        });

        //Handle form submission
        form.addEventListener('submit', function(e) {
            console.log('Form submit event triggered');

            //constant fields to validate
            const requiredFields = [
                'first_name',
                'last_name',
                'date_of_birth',
                'gender',
                'address',
                'admission_date',
                'admission_time',
                'history_date',
                'allergies',
                'duration_of_symptoms',
                'regular_medication',
                'dietary_habits',
                'elimination_habits',
                'sleep_patterns',
                'height',
                'weight',
                'BP_lft',
                'pulse',
                'strong',
                'temp_ranges'
            ];

            //Array to hold missing fields
            let missingFields = [];

            //Check all required fields
            requiredFields.forEach(function(fieldName) {
                const field = document.querySelector(`[name="${fieldName}"]`); //Get the field by name
                if (field && (!field.value || field.value.trim() === '')) { //Field is empty
                    missingFields.push(fieldName); //Add to missing fields

                    //Highlight the missing field
                    field.style.borderColor = 'red';
                    field.style.backgroundColor = '#ffe6e6';
                } else if (field) { //if field is filled, reset styles
                    field.style.borderColor = '';
                    field.style.backgroundColor = '';
                }
            });

            //If there are missing fields, prevent submission
            if (missingFields.length > 0) {
                e.preventDefault();
                alert('Please fill in all required fields:\n' + missingFields.join('\n'));

                //Re-enable button if validation fails
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Save Patient';
                }

                return false;
            }

            console.log('Form validation passed, submitting...');
            //Allow form to submit naturally
            return true;
        });
    }
});

//Save form data to localStorage
function saveFormData() {
    const form = document.getElementById('add-patient-form');
    if (!form) return; //If form not found, exit

    //Object to hold form data
    const formData = {};
    form.querySelectorAll('input, textarea, select').forEach(function(field) { //loop through all fields
        if (field.type === 'checkbox' || field.type === 'radio') {
            if (field.checked) { //save the checked fields only and exclude the unchecked and buttons
                formData[field.name] = field.value;
            }
        } else if (field.type !== 'submit' && field.type !== 'button') {
            formData[field.name] = field.value;
        }
    });

    localStorage.setItem('formData', JSON.stringify(formData));
    console.log('Form data saved to localStorage');
}

//Load form data from localStorage
function loadFormData() {
    const form = document.getElementById('add-patient-form');
    if (!form) return;

    const savedData = localStorage.getItem('formData');
    if (!savedData) return;

    try {
        const formData = JSON.parse(savedData);
        console.log('Loading saved form data...');

        Object.keys(formData).forEach(function(fieldName) {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (field) {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    if (field.value === formData[fieldName]) {
                        field.checked = true;
                    }
                } else {
                    field.value = formData[fieldName];
                }
            }
        });
    } catch(e) {
        console.error('Error loading form data:', e);
    }
}

//Clear saved form data after successful submission
function clearFormData() {
    localStorage.removeItem('formData');
    console.log('Form data cleared from localStorage');
}
