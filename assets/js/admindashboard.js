document.addEventListener('DOMContentLoaded', () => {
    const userIcon = document.querySelector('.user-profile-icon');
    const dropdownMenu = document.querySelector('.dropdown-menu');

        userIcon.addEventListener('click', (event) => {
            event.stopPropagation();
            console.log("User icon clicked"); // debug
            dropdownMenu.classList.toggle('show');
        });

        document.addEventListener('click', (event) => {
            setTimeout(() => {
                if (!userIcon.contains(event.target)) {
                    dropdownMenu.classList.remove('show');
                }
            }, 50);
        });

    // Placeholder for tile click handling
    const tiles = document.querySelectorAll('.tile');
    tiles.forEach(tile => {
        tile.addEventListener('click', () => {
            const label = tile.querySelector('.tile-label').textContent;
            console.log(`Navigating to: ${label} section`);
            // In a real application, you'd navigate here:
            // window.location.href = `/admin/${label.toLowerCase().replace(/\s+/g, '-')}`;
        });
    });
});