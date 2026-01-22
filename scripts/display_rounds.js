const picker = document.getElementById('round-picker');
const matches = document.querySelectorAll('.match-card');

function filterMatches() {
    const selectedRound = picker.value;

    matches.forEach(card => {
        if (card.dataset.round === selectedRound) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

filterMatches();
picker.addEventListener('change', filterMatches);