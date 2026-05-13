document.addEventListener('DOMContentLoaded',()=>{

const topImage = document.getElementById("topImage");

    window.addEventListener("scroll", () => {
    const scrollTop = window.scrollY;
    const maxScroll = document.body.scrollHeight - window.innerHeight;

    let progress = scrollTop / maxScroll;

    // convert to percentage
    let percent = 100 - (progress * 100);

    // reveal from bottom
    if (topImage) {
      topImage.style.clipPath = `inset(${percent}% 0 0 0)`;
    }
});

const classStatus = document.getElementById('classStatus');
const class_name = document.getElementById('class_name') ?? "";
let timeoutId;

if (class_name) {
  class_name.addEventListener('input',function(){
                const name = this.value;
                if (timeoutId) {
                    clearTimeout(timeoutId);
                }
                class_name.setCustomValidity("");
                classStatus.textContent = 'Checking validity...';
                classStatus.className = 'class-status checking';
                
                if (!/^[a-zA-Z][a-zA-Z0-9_.]*$/.test(name)) {
                    classStatus.textContent = 'Class Name can only contain letters, numbers, underscores and dots';
                    class_name.setCustomValidity("Class Name can only contain letters, numbers, underscores and dots and 1 @");
                    classStatus.className = 'class-status unavailable';
                    
                    return;
                }    
                    
                // Set timeout to avoid too many requests
                timeoutId = setTimeout(() => {
                    checkClassName(name);
                }, 500);

})
}
function checkClassName(className){
     fetch('../../src/APIs/UserAPI.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action : 'check-className',
                        class_name: className })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.valid) {
                        classStatus.textContent = data.errors.join(', ');
                        classStatus.className = 'Class-status unavailable';
                        
                    } else {
                        classStatus.textContent = 'Class Name is Valid!';
                        classStatus.className = 'class-name valid';
                        
                    } 
                })
            .catch(error => {
                console.error('Error:', error);
                classStatus.textContent = 'Error checking class name. Please try again.';
                classStatus.className = 'class-status unavailable';
            });
}
});