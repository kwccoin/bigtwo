<?php
	$db = new PDO('mysql:host=www.shop151.ierg4210.org;dbname=bigtwo', "bigtwoadmin", "csci4140");
	$q = $db -> prepare("SELECT * FROM queue WHERE userid = ? AND valid = 1");
	$q->execute(array($_REQUEST["userid"]));
	$r = $q->fetch();
	if (!$r) {
		$q = $db -> prepare("INSERT INTO queue (userid) VALUES (?)");
		$q->execute(array($_REQUEST["userid"]));
	}
	error_log("visit flag");
?>
<html>
<style type="text/css">
#roomList > div {border: black solid 1px; margin: 2px; width: 50px;}
</style>
<body>
	Welcome to the waiting Room of the game.<br>This is our queue now.<br><br>
	Room List (click to select a room):<br><br><div id="roomList"></div><br>
	<input type="button" id="newRoomButton" value="Create New Room"></input><br><br>
	Friends in this room.
	<div id="queue"></div>
</body>
<script type="text/javascript" src="incl/jquery.js"></script>
<script type="text/javascript">
	var currentRoom=0;

	window.onbeforeunload = function(){
		quitQueue();
	}

	var periodicReload = setInterval(function(){
		refreshRoomList();
		refreshQueue();
		if ($("#queue").children().length >= 4){
			setTimeout(function(){
				quitQueue();
				$(window).off('beforeunload');				
				window.location.href = "room.php";
			}, 2000);
		}
	}, 1000);

	$("#newRoomButton").on("click", function(){createRoom();});

	function quitQueue(){
		$.ajax({
			url: "lobby-process.php",
			type: "POST",
			async: false,
			data: {action: "removeFromQueue", userid: "<?php echo $_REQUEST['userid']; ?>"} 
		});
		return "You have left the game lobby.";
	};

	function refreshRoomList(){
		$.ajax({
			url: "lobby-process.php",
			type: "POST",
			data: {action: "getRoomList"} 
		}).done(function(list){
			$("#roomList").html("");
			for (room in list){
				if (list[room].roomid != null){
					var t=$("<div onclick='joinRoom(this.innerHTML);console.log(\"joined room \"+this.innerHTML)'>"+list[room].roomid+"</div>")
					$("#roomList").append(t);					
				}
			}
		})
	}

	function refreshQueue(){
		$.ajax({
			url: "lobby-process.php",
			type: "POST",
			data: {action: "getCurrentQueue", roomid: currentRoom} 
		}).done(function(list){
			$("#queue").html("");
			for (player in list){
				var t=$("<div>"+list[player].userid+"</div>")
				$("#queue").append(t);
			}
		})
	}

	function createRoom(){
		$.ajax({
			url: "lobby-process.php",
			type: "POST",
			data: {action: "createRoom"} 
		}).done(function(createdRoom){
				joinRoom(createdRoom);
		});
	}

	function joinRoom(roomNumber){
		currentRoom = roomNumber;
		$.ajax({
			url: "lobby-process.php",
			type: "POST",
			data: {action: "joinRoom", userid: "<?php echo $_REQUEST['userid']; ?>", roomid: roomNumber} 
		});		
	}		
</script>
</html>