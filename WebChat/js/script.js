$().ready(function() {
	
	var chat = (function() {
	
		var fn = {};
		
		var runtime = {
			nick: null,
			listen: true,
			channels: { }
		};
		
		var conf = {
			
			wrappers: {
				tabs: $('#tabs'),
				output: $('#output')
			},
			
			tpl: {
				tab: '<li class="tab">{title} <span class="close">&times;</span></li>',
				output: '<div class="channel"><table /><ul class="names" /><input type="text" class="send" value="" /></div>',
				msg: '<tr class="type-{type}"><td class="time">{time}</td><td class="nick">{nick}</td><td class="msg">{msg}</td>'
			}
		};
		
		fn.util = {
			
			replace: function(text, subst) {
				$.each(subst, function(token, value) {
					text = text.replace(token, value);
				});
				return text;
			},
			
			formatTime: function(timestamp) {
				if(typeof timestamp === 'number') {
					var date = new Date(timestamp * 1000);
					return ('0' + date.getHours()).slice(-2) + ':' + ('0' + date.getMinutes()).slice(-2);
				} else {
					return '';
				}
			},
			
			getCommand: function(text) {
				if(text[0] === '/') {
					return /^\/([^ ]*) ?(.*)$/.exec(text).slice(1);
				}
				return false;
			},
			
			escapeHTML: function(text) {
				return $('<div />').text(text).html();
			}
			
		};
		
		fn.handler = {
			
			setTab: function() {
				fn.ui.setChannel($(this).data('channel'));
			},
			
			closeTab: function(e) {
				e.stopPropagation();
				var name = $(this).parent('.tab').data('channel');
				if(typeof runtime.channels[name] !== 'undefined') {
					fn.api.part(name);
				};
			},
			
			send: function(e) {
				if(e.which === 13) {
					var el = $(this);
					var channel = el.parents('.channel').data('channel');
					var cmd = fn.util.getCommand(el.val());
					if(cmd !== false) {
						if(typeof fn.prepareCmd[cmd[0]] !== 'undefined') {
							fn.api.raw(cmd[0], fn.prepareCmd[ cmd[0] ](cmd[1]));
						}
						else {
							fn.ui.addMsg(channel, 'error', '', '', 'Unsupported command');
						}
					} else {
						var val = $.trim(el.val());
						if(val.length) {
							fn.api.send(channel, val);
						};
					}
					el.val('');
				};
			}
			
		};
		
		fn.ui = {
		
			addChannel: function(name) {
				runtime.channels[name] = {
					tab: $(fn.util.replace(conf.tpl.tab, { '{title}': name }))
						.data('channel', name)
						.appendTo(conf.wrappers.tabs),
					output: $(conf.tpl.output)
						.data('channel', name)
						.appendTo(conf.wrappers.output)
				};
			},
			
			isChannel: function(name) {
				return typeof runtime.channels[name] !== 'undefined';
			},
			
			getChannel: function() {
				var name = conf.wrappers.tabs.find('.active').data('channel');
				if(fn.ui.isChannel(name)) {
					return name;
				} else {
					return false;
				}
			},

			removeChannel: function(name) {
				var active = fn.ui.getChannel();
				if(fn.ui.isChannel(name)) {
					runtime.channels[name].tab.add(runtime.channels[name].output).remove();
					delete runtime.channels[name];
				}
				if(active === name) {
					fn.ui.setFirstChannel();
				}
			},
			
			setFirstChannel: function() {
				$.each(runtime.channels, function(name) {
					fn.ui.setChannel(name);
					return false;
				});
			},
			
			setChannel: function(name) {
				var active = fn.ui.getChannel();
				if(active !== name && fn.ui.isChannel(name)) {
					if(active !== false) {
						runtime.channels[active].tab.removeClass('active');
						runtime.channels[active].output.removeClass('active').find('input.send').blur();
					}
					runtime.channels[name].tab.add(runtime.channels[name].output).addClass('active').find('input.send').focus();
				};
			},
			
			addMsg: function(channel, type, time, nick, msg) {
				if(fn.ui.isChannel(channel)) {
					var tokens = {
						'{type}': type,
						'{time}': fn.util.formatTime(time),
						'{nick}': fn.util.escapeHTML(nick),
						'{msg}': fn.util.escapeHTML(msg)
					};
					runtime.channels[channel].output
					.find('table')
					.append($(fn.util.replace(conf.tpl.msg, tokens)));
				};
			},
			
			processEvent: function(event) {
				if(typeof fn.process[event.type] !== 'undefined') {
					fn.process[event.type](event);
				};
			},
			
			isSelf: function(nick) {
				return nick === runtime.nick;
			}
		
		};
		
		fn.prepareCmd = {
			join: function(data) {
				return {channel: data};
			},

			part: function(data) {
				return {channel: data};
			}

		};
		
		fn.process = {
			
			join: function(event) {
				if(fn.ui.isSelf(event.nick)) {
					fn.ui.addChannel(event.channel);
					fn.ui.setChannel(event.channel);
				} else {
					var msg = event.nick + ' (' + event.username + '@' + event.host +') has joined';
					fn.ui.addMsg(event.channel, event.type, event.time, '', msg);
				};
			},

			part: function(event) {
				if(fn.ui.isSelf(event.nick)) {
					fn.ui.removeChannel(event.channel);
				} else {
					var msg = event.nick + ' (' + event.username + '@' + event.host +') has left';
					fn.ui.addMsg(event.channel, event.type, event.time, '', msg);
				};
			},

			quit: function(event) {
				if(fn.ui.isSelf(event.nick)) {
					fn.stop();
					$.each(runtime.channels, function(name) {
						fn.ui.removeChannel(name);
					});
				} else {
					var msg = event.nick + ' (' + event.username + '@' + event.host +') has quit';
					fn.ui.addMsg(event.channel, event.type, event.time, '', msg);
				};
			},

			privmsg: function(event) {
				fn.ui.addMsg(event.channel, event.type, event.time, event.nick, event.msg);
			}
		};
		
		fn.api = {

			raw: function(action, data) {
				return $.post('', {action: action, args: data}, function(data) {console.log(action, data);}, 'json');
			},
			
			joined: function() {
				return fn.api.raw('joined');
			},
			
			join: function(channel) {
				return fn.api.raw('join', { channel: channel });
			},
			
			part: function(channel) {
				return fn.api.raw('part', { channel: channel });
			},
			
			send: function(channel, msg) {
				return fn.api.raw('send', { channel: channel, msg: msg });
			},
			
			names: function(channel) {
				return fn.api.raw('names', { channel: channel });
			},
			
			connection: function() {
				return fn.api.raw('connection');
			},
			
			update: function() {
				return fn.api.raw('get', { }).then(function(data) {
					$.each(data, function(index, event) {
						fn.ui.processEvent(event);
					});
				});
			}
			
		};
		
		fn.init = function() {

			conf.wrappers.tabs.on('click', '.tab:not(.active, .close)', fn.handler.setTab)
			conf.wrappers.tabs.on('click', '.tab  .close', fn.handler.closeTab);
			conf.wrappers.output.on('keydown', 'input.send', fn.handler.send);
			
			fn.api.connection()
			.done(function(data) {
				runtime.nick = data.nick;
			});
			
			fn.api.joined()
			.done(function(data) {
				$.each(data, function(index, name) {
					fn.ui.addChannel(name);
				});
				fn.ui.setFirstChannel();
			});
			
			fn.listen();
		};
		
		fn.listen = function() {
			fn.api.update().done(function() {
				if(runtime.listen === true) {
					fn.listen();
				} else {
					runtime.listen = true;
				}
			});
		};
		
		fn.stop = function() {
			runtime.listen = false;
		}
		
		return {
			init: fn.init,
			api: fn.api,
			ui: fn.ui,
			runtime: runtime,
			listen: fn.listen,
			stop: fn.stop
		}
	
	}) ();
	
	chat.init();
	window.chat = chat;
	
});