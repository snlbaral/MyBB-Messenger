var handle;var handle2;var handle4;function chatTab() {document.getElementsByClassName('tab2BuddyList')[0].style.display = 'none';document.getElementsByClassName('tab1ChatList')[0].style.display = 'initial';var chatdiv = document.getElementsByClassName('chatTab')[0];var activediv = document.getElementsByClassName('activeTab')[0];document.getElementsByClassName('chatTab')[0].style.background = '#f5f5ff';document.getElementsByClassName('activeTab')[0].style.background = 'initial';chatdiv.style.color = "#000";activediv.style.color = "#fff";activediv.style.border = "0";chatdiv.style.fontWeight = "bold";activediv.style.fontWeight = "normal";handle = setInterval(function() {$("#chatlist_tabs").load(location.href + " #tabs");}, 5000);}function activeTab() {clearInterval(handle);clearInterval(handle2);clearInterval(handle4);document.getElementById('tab2BuddyList').style.display = 'initial';document.getElementById('tab1ChatList').style.display = 'none';var chatdiv = document.getElementById('chatTab');var activediv = document.getElementById('activeTab');chatdiv.style.background = 'initial';activediv.style.background = '#f5f5ff';chatdiv.style.color = "#fff";activediv.style.color = "#000";chatdiv.style.border = "0";activediv.style.fontWeight = "bold";chatdiv.style.fontWeight = "normal";}function oChatHead(e) {clearInterval(handle2);clearInterval(handle);clearInterval(handle4);var toid = e.getAttribute('toid');var fromid = e.getAttribute('fromid');document.getElementById("chatlist_tabs").style.display = "none";document.getElementById("pChat").style.display = "initial";var fde = new FormData();fde.append('toid',toid);fde.append('action',"request");$.ajax({url: "messenger.php",type: "POST",data: fde,dataType: 'json',success: function(data) {if(data.response=="Exist") {$("body").load("#pMainHead", function() {clearInterval(handle2);clearInterval(handle4);clearInterval(handle);document.getElementById("activeTab").style.display = "none";document.getElementById("chatTab").style.display = "none";document.getElementById("dum").style.display = "block";var imgel = document.createElement('img');imgel.src = data.avatar;imgel.classList.add('chatTitleImg');document.getElementById('dum').appendChild(imgel);var udiv = document.createElement('div');udiv.classList.add('udiv');udiv.innerHTML = data.username;document.getElementById('dum').appendChild(udiv);document.getElementById("chatlist_tabs").style.display = "none";document.getElementById("pMainHead").style.display = "block";document.getElementById("pChat").style.display = "block";var a = document.getElementById("pChild");a.scrollTop = a.scrollHeight;});} else {clearInterval(handle2);clearInterval(handle);clearInterval(handle4);var toid = data.toid;var fromid = data.fromid;var tousername = data.username;var toavatar = data.avatar;document.getElementById("pMainHead").style.display = "none";document.getElementById("activeTab").style.display = "none";document.getElementById("chatTab").style.display = "none";document.getElementById("dum").style.display = "block";var imgel = document.createElement('img');imgel.src = data.avatar;imgel.classList.add('chatTitleImg');document.getElementById('dum').appendChild(imgel);var udiv = document.createElement('div');udiv.classList.add('udiv');udiv.innerHTML = data.username;document.getElementById('dum').appendChild(udiv);var mainDiv = document.getElementById("pAHead");var wholechatdiv = document.createElement('div');wholechatdiv.classList.add('Awholechatdiv');var img = document.createElement('img');img.src = toavatar;wholechatdiv.appendChild(img);var childDiv = document.createElement('div');childDiv.classList.add("childUsername");childDiv.innerHTML = tousername;wholechatdiv.appendChild(childDiv);mainDiv.appendChild(wholechatdiv);var formDiv = document.createElement('div');var createform = document.createElement('form');createform.setAttribute("action", "");createform.setAttribute("method", "post");createform.id = "aChatForm";var inputelement = document.createElement('input');inputelement.setAttribute("type", "text");inputelement.setAttribute("name", "toid");inputelement.setAttribute("value", toid);inputelement.style.display = "none";createform.appendChild(inputelement);var inputelement2 = document.createElement('input');inputelement2.setAttribute("type", "text");inputelement2.setAttribute("name", "fromid");inputelement2.setAttribute("value", fromid);inputelement2.style.display = "none";createform.appendChild(inputelement2);var inputelement3 = document.createElement('input');inputelement3.setAttribute("type", "text");inputelement3.setAttribute("name", "message");inputelement3.setAttribute("required","on");createform.appendChild(inputelement3);var inputelement4 = document.createElement('input');inputelement4.setAttribute("type","hidden");inputelement4.setAttribute("name","action");inputelement4.setAttribute("value","messagesend");createform.appendChild(inputelement4);var submitelement = document.createElement('input');submitelement.setAttribute("type", "submit");submitelement.setAttribute("name", "submit");submitelement.setAttribute("value", "Send");submitelement.id = "tempdata";createform.appendChild(submitelement);formDiv.appendChild(createform);mainDiv.appendChild(formDiv);$("#aChatForm").submit(function(e) {e.preventDefault();var fde = new FormData(this);$.ajax({url: 'messenger.php',type: 'POST',data: fde,dataType: 'json',success: function(data) {$('#aChatForm input[type="text"]').val('');if(data.response=="success") {returnToList();$("#chatlist_tabs").load(location.href + " #tabs");chatTab();} else {}},error: function(e) {},cache: false,contentType: false,processData: false,});});}},error: function(e) {},cache: false,contentType: false,processData: false,});}$("#pmhForm").submit(function(e) {e.preventDefault();var fde = new FormData(this);var toid = document.getElementById("recentchatid").innerHTML;fde.append('toid',toid);$.ajax({url: 'messenger.php',type: 'POST',data: fde,dataType: 'json',success: function(data) {$('#pmhForm input[type="text"]').val('');if(data.response=="success") {$("body").load("#pMainHead", function() {document.getElementById("activeTab").style.display = "none";document.getElementById("chatTab").style.display = "none";document.getElementById("dum").style.display = "block";var imgel = document.createElement('img');imgel.src = data.avatar;imgel.classList.add('chatTitleImg');document.getElementById('dum').appendChild(imgel);var udiv = document.createElement('div');udiv.classList.add('udiv');udiv.innerHTML = data.username;document.getElementById('dum').appendChild(udiv); 			document.getElementById("chatlist_tabs").style.display = "none";document.getElementById("pMainHead").style.display = "block";document.getElementById("pChat").style.display = "block";var a = document.getElementById("pChild");a.scrollTop = a.scrollHeight;$("#pmhMessage").focus();});} else {}},error: function(e) {},cache: false,contentType: false,processData: false,});});handle2 = setInterval(function() {$("#chatlist_tabs").load(location.href + " #tabs");}, 5000);var handle3 = setInterval(function() {$("#pChild").load(location.href + " #secondChild");var a = document.getElementById("pChild");if((a.scrollTop+420)<a.scrollHeight) {a.scrollTop = a.scrollTop;} else {a.scrollTop = a.scrollHeight;}}, 5000);function returnToList() {document.getElementById('chatlist_tabs').style.display = "block";document.getElementById('pChat').style.display = "none";document.getElementById('dum').style.display = "none";document.getElementById('activeTab').style.display = "inline-block";document.getElementById('chatTab').style.display = "inline-block";if(!document.getElementsByClassName('Awholechatdiv')[0]) {} else {var a = document.getElementsByClassName('Awholechatdiv')[0];a.parentNode.removeChild(a);}if(!document.getElementById('aChatForm')) {} else {var d = document.getElementById('aChatForm');d.parentNode.removeChild(d);}var b = document.getElementsByClassName("chatTitleImg")[0];b.parentNode.removeChild(b);var c = document.getElementsByClassName("udiv")[0];c.parentNode.removeChild(c);handle4 = setInterval(function() {$("#chatlist_tabs").load(location.href + " #tabs");}, 5000);}if(typeof idn !== 'undefined') {clearInterval(idn);}