let selectedUser = 0;

function loadUsers() {
  fetch("/Findex/api/get_users.php")
    .then(res => res.text())
    .then(data => {
      document.getElementById("usersList").innerHTML = data;
    });
}

function selectUser(id, name, event) {
  selectedUser = id;

  // Update header
  document.getElementById("chatWith").innerText = name;

  // Remove active from all
  document.querySelectorAll('.user').forEach(u => u.classList.remove('active'));

  // Add active to clicked user
  if (event) {
    event.currentTarget.classList.add('active');
  }

  loadMessages();
}

function loadMessages() {
  if (!selectedUser) return;

  fetch("/Findex/api/get_messages.php?user=" + selectedUser)
    .then(res => res.text())
    .then(data => {
      document.getElementById("messages").innerHTML = data;
    });
}

function sendMessage() {
  const msg = document.getElementById("msg").value;

  if (!msg || !selectedUser) return;

  fetch("/Findex/api/send_message.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded"
    },
    body: "message=" + encodeURIComponent(msg) + "&receiver=" + selectedUser
  })
  .then(() => {
    document.getElementById("msg").value = "";
    loadMessages();
  });
}

setInterval(loadMessages, 1000);
loadUsers();