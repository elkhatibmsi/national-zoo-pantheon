document.addEventListener('alpine:init', () => {
    Alpine.data('h_tabs', () => ({
        openTab: 1,
        activeClasses: 'text-darkergray border-sizzling-red font-bold border-b-2',
        inactiveClasses: ' text-darkergray border-none',   
    })
    );

});

