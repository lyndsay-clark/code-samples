function Inboxer() {
	var self = this;

	self.unreadCount = 0;

	self.update = function() {
		$.get("/scripts/message/unread-messages.php", function(res) {
			res = $.parseJSON(res);
			self.unreadCount = Number($.trim(res.count));
			self.display();
		});
	}
	self.display = function() {
		$(".inboxer-display").each(function() {
			var count = self.unreadCount;

			$(this).find(".count").text(String(count));
			
			if(count > 0) {
				$(this).show();
				$(this).removeClass("inboxer-empty");
			} else if(!$(this).hasClass("inboxer-keep-visible")) {
				$(this).hide();
				$(this).addClass("inboxer-empty");
			}
		});
	}
	self.appendMessage = function($to, m) {
		var username = $("input[name='session_username']").val();

		$msg = $("<div>", { class: "message-text" }).prepend(
			$("<div>", { class: "sender-info"+(username == m.sender ? " you": "") }).append(
				$("<img>", { src: m.avatar }),
				$("<span>", { class: "sender-username" }).text(m.sender)
			)
		).attr("data-inboxer-i", m.i)

		var $content = $("<span>", { text: m.content });
		$content.html($($content).text().replace(/\n/g, "<br/>"));

		$msg.append($content);

		$to.append($msg);
	}
	self.updateConversation = function(page, success) {
		var $id = $("#inboxer-conv-id");
		var $out = $(".inboxer-output");
		var $messages = $("<div>", { class: "message-bundle" });
		page = page || 1;

		if($id.length == 1) {
			if($id.hasClass("inboxer-in-conv")) {
				$.post("/scripts/message/get-conversation.php", {
						id: $id.val(),
						page: page
					},
					function(res) {
						res = $.parseJSON(res);
						$(".inboxer-more").remove();
						if(res.remaining > 0) {
							$out.prepend($("<div>", { class: "inboxer-more" })
								.append($("<a>")
									.text("Show "+res.remaining+" more messages")
									.click(function() {
										self.updateConversation(page + 1);
									})
								)
							);
						}
						for(var i in res.messages) {
							self.appendMessage($messages, res.messages[i]);
						}
						success();
					}
				);
			}
		}

		$out.prepend($messages);
	}

	setInterval(self.update, 10000);
	self.update();
};


