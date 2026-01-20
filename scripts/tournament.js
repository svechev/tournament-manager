const createRadio = document.querySelector('input[value="create"]');
const joinRadio = document.querySelector('input[value="join"]');

const createBox = document.getElementById('create-team-box');
const joinBox = document.getElementById('join-team-box');

const newTeamInput = document.querySelector('input[name="new_team_name"]');
const teamSelect = document.querySelector('select[name="team_name"]');

function toggleTeamBoxes() {
    if (createRadio.checked) {
        createBox.style.display = 'block';
        joinBox.style.display = 'none';
    } 
    else {
        createBox.style.display = 'none';
        joinBox.style.display = 'block';
    }
}

createRadio.addEventListener('change', toggleTeamBoxes);
joinRadio.addEventListener('change', toggleTeamBoxes);

toggleTeamBoxes();