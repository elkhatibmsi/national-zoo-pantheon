(function ($, Drupal) { 
    $(document).ready(function(){
        const listItem = document.querySelectorAll('#iucn-list li');
        const selectedItem = document.querySelector("#iucn-list").getAttribute("data-designation");
        
        listItem.forEach((ele) => {
        let label = ele.getAttribute("class");
         let button = document.createElement("span");
          button.className = label;
          let text = document.createTextNode(label);
          button.appendChild(text);
          ele.insertBefore(button, ele.children[0]);
          if(ele.textContent.includes(selectedItem)) {
            button.classList.add("active");
          }
        });
            
        
    });
    
    
})(jQuery, Drupal);