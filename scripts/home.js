const grid = document.getElementById('tournament-grid');
const cards = Array.from(grid.querySelectorAll('.tournament-card'));
const categoryFilter = document.getElementById('category-filter');
const sortDate = document.getElementById('sort-date');
const statusFilter = document.getElementById('status-filter');

function updateTournaments() {
const cat = categoryFilter.value;
const order = sortDate.value;
const stat = statusFilter.value;

cards.forEach(card => {
    const cardCat = card.dataset.category;
    const cardStat = card.dataset.status;
    card.style.display = ((!cat || cardCat === cat)&&(!stat || cardStat === stat)) ? 'block' : 'none';
});

const visibleCards = cards.filter(c => c.style.display !== 'none');
visibleCards.sort((a, b) => {
    const aTime = parseInt(a.dataset.date);
    const bTime = parseInt(b.dataset.date);
    return order === 'asc' ? aTime - bTime : bTime - aTime;
});

visibleCards.forEach(c => grid.appendChild(c));
}
statusFilter.addEventListener('change',updateTournaments);
categoryFilter.addEventListener('change', updateTournaments);
sortDate.addEventListener('change', updateTournaments);
updateTournaments(); 