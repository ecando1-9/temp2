<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('location:login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$chat_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$user_id = $_SESSION['user_id'];
$chat_user_id = $_GET['user_id'];

// Mark messages as read where current user is the recipient
$mark_read_query = "UPDATE chat_messages SET is_read = 1 WHERE user1_id = ? AND user2_id = ? AND is_read = 0";
$stmt = $conn->prepare($mark_read_query);
$stmt->bind_param("ii", $chat_user_id, $user_id);
$stmt->execute();
$stmt->close();

// Fetch chat history between the current user and the selected user
$query = "
    SELECT cm.message, cm.sender_id, cm.timestamp 
    FROM chat_messages cm 
    WHERE (cm.user1_id = '$user_id' AND cm.user2_id = '$chat_user_id') 
    OR (cm.user1_id = '$chat_user_id' AND cm.user2_id = '$user_id')
    ORDER BY cm.timestamp ASC
";
$result = mysqli_query($conn, $query);
$messages = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Fetch chat user details
$query_user = "SELECT name, image FROM user_form WHERE id = '$chat_user_id'";
$result_user = mysqli_query($conn, $query_user);
$chat_user = mysqli_fetch_assoc($result_user);

if (!$chat_user) {
    echo '<p>User not found. <a href="chat.php">Go back to chats</a></p>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> <?php echo htmlspecialchars($chat_user['name']); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="chat-container7">
        <div class="profile-section">
            <div class="profile-icon">
                <?php
                    if ($chat_user['image'] == '') {
                        echo '<img src="images/default-avatar.png" class="profile-pic">';
                    } else {
                        echo '<img src="uploaded_img/'.$chat_user['image'].'" class="profile-pic">';
                    }
                ?>
            </div>
            <h4>Chat with <?php echo htmlspecialchars($chat_user['name']); ?></h4>
        </div>

        <div class="back-section">
            <a href="chat.php">Back</a>
        </div>
       

        <div class="chat-area7">
            <div id="messages">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo $message['sender_id'] === $user_id ? 'sent' : 'received'; ?>">
                        <span><?php echo htmlspecialchars($message['message']); ?></span>
                        <small class="timestamp"><?php echo htmlspecialchars(date('H:i, Y-m-d', strtotime($message['timestamp']))); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
            


            <input type="text" id="messageInput" placeholder="Type a message">
            <button id="sendMessage">Send</button>
        </div>
        <button id="floatButton">To Message</button>
    </div>
    

    <script>
        const currentUserId = <?php echo json_encode($user_id); ?>;
        const chatUserId = <?php echo json_encode($chat_user_id); ?>;
        const messageInput = document.getElementById('messageInput');
        const sendMessageButton = document.getElementById('sendMessage');
        const messagesContainer = document.getElementById('messages');

        // Function to send a message
        function sendMessage() {
            const message = messageInput.value.trim();

            if (message.length > 0) {
                fetch('send_message.php', {
                    method: 'POST',
                    body: JSON.stringify({ message, user_id: chatUserId }),
                    headers: { 'Content-Type': 'application/json' }
                }).then(() => {
                    messagesContainer.innerHTML += `<div class="message sent">
                                                        <span>${message}</span>
                                                        <small class="timestamp">${new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })}, ${new Date().toLocaleDateString('en-GB')}</small>
                                                    </div>`;
                    messageInput.value = ''; // Clear the input field
                    messagesContainer.scrollTop = messagesContainer.scrollHeight; // Scroll to the bottom
                });
            }
        }

        // Event listener for 'Enter' key press
        messageInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Prevent the default behavior of Enter key
                sendMessage(); // Trigger the send message function
            }
        });

        // Event listener for 'Send' button click
        sendMessageButton.addEventListener('click', function() {
            sendMessage(); // Trigger the send message function when button is clicked
        });

        // Load chat messages initially
        function loadChat() {
            fetch(`get_chat.php?user_id=${chatUserId}`)
                .then(response => response.json())
                .then(messages => {
                    let chatHtml = '';
                    messages.forEach(message => {
                        const messageClass = message.sender_id === currentUserId ? 'sent' : 'received';
                        chatHtml += `<div class="message ${messageClass}">
                                        <span>${message.message}</span>
                                        <small class="timestamp">${new Date(message.timestamp).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })}, ${new Date(message.timestamp).toLocaleDateString('en-GB')}</small>
                                     </div>`;
                    });
                    messagesContainer.innerHTML = chatHtml;
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                });
        }

        // Prevent message from being sent when clicking anywhere else on the screen
        document.addEventListener('click', function(event) {
            if (!messageInput.contains(event.target) && !sendMessageButton.contains(event.target)) {
                messageInput.blur(); // Remove focus from the input when clicking outside
            }
        });
        setInterval(loadChat, 2000);

        // Initial chat load
        loadChat();
        document.addEventListener('DOMContentLoaded', function() {
    const floatButton = document.getElementById('floatButton');
    const chatArea = document.querySelector('.chat-area7');
    const messageInput = document.getElementById('messageInput');

    if (!chatArea || !messageInput) {
        console.error('Chat area or message input not found!');
        return;
    }

    floatButton.addEventListener('click', function() {
        chatArea.scrollTo({
            top: chatArea.scrollHeight,
            behavior: 'smooth'
        });

        setTimeout(() => {
            messageInput.focus();
        }, 100);
    });
});

    </script>

</body>
</html>