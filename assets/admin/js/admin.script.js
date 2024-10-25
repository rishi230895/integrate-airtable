document.addEventListener('DOMContentLoaded', function() {
    const selects = document.querySelectorAll('.column-select');
    const restrictedValues = ['title', 'desc', 'feature_img', 'taxonomy'];
    if( selects  ) {
        selects.forEach(select => {
           
            select.addEventListener('change', function() {
                const selectedValue = this.value;
                if (restrictedValues.includes(selectedValue)) {
                    selects.forEach(otherSelect => {
                        if (otherSelect !== this && restrictedValues.includes(otherSelect.value) && otherSelect.value === selectedValue) {
                            otherSelect.selectedIndex = 0; 
                        }
                    });
                }
            });
        });
    }  
    

    /** Developer section  */

    let copyBtns = document.querySelectorAll('.copy-btn');
    copyBtns.forEach(button => {
        button.addEventListener('click', function() {
            const target = this.getAttribute('data-target');
            const tempInput = document.createElement('input');
            document.body.appendChild(tempInput);
            tempInput.value = target;
            tempInput.select();
            document.execCommand('copy');
            
            const buttonRef = this; // Store the button reference
            buttonRef.innerText = 'Coping...'; // Change button text to "Coping..."
            
            setTimeout(function() {
                buttonRef.innerText = 'Copy shortcode'; // Restore button text
            }, 250);
            
            document.body.removeChild(tempInput); // Move this outside of setTimeout
        });
    });

    
});


