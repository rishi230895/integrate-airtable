document.addEventListener('DOMContentLoaded', function() {
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
});