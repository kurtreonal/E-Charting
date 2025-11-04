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

//radio deselector function
document.addEventListener("DOMContentLoaded", () => {
  const radios = document.querySelectorAll('input[type="radio"].toggle-radio');

  radios.forEach(radio => {
    radio.addEventListener("click", function (e) {
      //if its already checked, uncheck it
      if (this.previousChecked) {
        this.checked = false;
      }
      //store current checked state
      this.previousChecked = this.checked;

      //clear previousChecked for other radios with the same name
      radios.forEach(r => {
        if (r.name === this.name && r !== this) {
          r.previousChecked = false;
        }
      });
    });
  });
});