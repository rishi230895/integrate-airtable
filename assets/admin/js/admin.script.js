document.addEventListener('DOMContentLoaded', function() {

    /** Restricted select only for one time select field key */

    const selects = document.querySelectorAll('.column-select');
    const restrictedValues = ['title', 'desc', 'feature_img'];
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
    


    /** Developer section - Copy Shortcode on Copy button click... */

    let copyBtns = document.querySelectorAll('.copy-btn');
    copyBtns.forEach(button => {
        button.addEventListener('click', function() {
            const target = this.getAttribute('data-target');
            const tempInput = document.createElement('input');
            document.body.appendChild(tempInput);
            tempInput.value = target;
            tempInput.select();
            document.execCommand('copy');
            
            const buttonRef = this; 
            buttonRef.innerText = 'Coping...'; 
            
            setTimeout(function() {
                buttonRef.innerText = 'Copy shortcode'; 
            }, 250);
            
            document.body.removeChild(tempInput);
        });
    });
    
});