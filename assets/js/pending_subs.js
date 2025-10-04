document.querySelector('.profile-btn').addEventListener('click', function() {
        document.querySelector('.profile-dropdown').classList.toggle('show');
    });
    window.addEventListener('click', function(e) {
        if (!document.querySelector('.profile-dropdown').contains(e.target) && document.querySelector('.profile-dropdown').classList.contains('show')) {
            document.querySelector('.profile-dropdown').classList.remove('show');
        }
    });

    function searchMember() {
        const input = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.querySelectorAll('table tbody tr');
        rows.forEach(row => {
            row.style.display = row.cells[1].textContent.toLowerCase().includes(input) ? '' : 'none';
        });
    }