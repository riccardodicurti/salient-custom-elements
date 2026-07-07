(function () {
	'use strict';

	if (typeof sceGenerate === 'undefined') {
		return;
	}

	var generateForm = document.getElementById('sce-generate-form');
	var modifyForm = document.getElementById('sce-modify-form');

	if (generateForm) {
		initGenerateMode();
	}

	if (modifyForm && sceGenerate.elementId) {
		initModifyMode();
	}

	function initGenerateMode() {
		var chatLog = document.getElementById('sce-chat-log');
		var promptField = document.getElementById('sce-prompt');
		var btn = document.getElementById('sce-gen-btn');
		var generateLabel = sceGenerate.generate || 'Generate';
		var generatingLabel = sceGenerate.generating || 'Generating…';
		var completeLabel = sceGenerate.complete || 'Complete!';
		var errorLabel = sceGenerate.error || 'Error';

		generateForm.addEventListener('submit', function (e) {
			e.preventDefault();

			var prompt = promptField.value.trim();
			if (!prompt) {
				return;
			}

			btn.disabled = true;
			btn.textContent = generatingLabel;
			chatLog.hidden = false;
			chatLog.innerHTML = '';

			addBubble(chatLog, 'user', prompt);

			var stepsEl = document.createElement('div');
			stepsEl.className = 'sce-chat-steps';
			chatLog.appendChild(stepsEl);

			streamRequest(
				{
					prompt: prompt,
					nonce: sceGenerate.streamNonce
				},
				stepsEl,
				{
					onDone: function (data) {
						addStep(stepsEl, data.message || completeLabel, 'done');
						setTimeout(function () {
							if (data.edit_url) {
								window.location.href = data.edit_url;
							}
						}, 600);
					},
					onError: function () {
						resetButton(btn, generateLabel);
					},
					onComplete: function () {
						resetButton(btn, generateLabel);
					}
				}
			);
		});
	}

	function initModifyMode() {
		var chatLog = document.getElementById('sce-modify-chat-log');
		var promptField = document.getElementById('sce-modify-prompt');
		var btn = document.getElementById('sce-modify-btn');
		var modifyLabel = sceGenerate.modify || 'Apply changes';
		var modifyingLabel = sceGenerate.modifying || 'Modifying…';
		var modifyDoneLabel = sceGenerate.modifyDone || 'Element updated.';
		var errorLabel = sceGenerate.error || 'Error';
		var messages = [];
		var currentDefinition = sceGenerate.currentDefinition || null;

		if (sceGenerate.modifyPlaceholder && promptField) {
			promptField.placeholder = sceGenerate.modifyPlaceholder;
		}

		modifyForm.addEventListener('submit', function (e) {
			e.preventDefault();

			var prompt = promptField.value.trim();
			if (!prompt) {
				return;
			}

			btn.disabled = true;
			btn.textContent = modifyingLabel;

			addBubble(chatLog, 'user', prompt);
			scrollChatToBottom(chatLog);

			var stepsEl = document.createElement('div');
			stepsEl.className = 'sce-chat-steps';
			chatLog.appendChild(stepsEl);

			streamRequest(
				{
					prompt: prompt,
					nonce: sceGenerate.streamNonce,
					element_id: sceGenerate.elementId,
					messages: messages
				},
				stepsEl,
				{
					onDone: function (data) {
						addStep(stepsEl, data.message || modifyDoneLabel, 'done');

						if (data.definition) {
							updateEditForm(data.definition);
							currentDefinition = data.definition;
						}

						var assistantMessage = data.message || modifyDoneLabel;
						addBubble(chatLog, 'assistant', assistantMessage);

						messages.push({ role: 'user', content: prompt });
						messages.push({ role: 'assistant', content: assistantMessage });

						promptField.value = '';
						scrollChatToBottom(chatLog);
					},
					onError: function () {
						resetButton(btn, modifyLabel);
					},
					onComplete: function () {
						resetButton(btn, modifyLabel);
					}
				}
			);
		});
	}

	function streamRequest(payload, stepsEl, handlers) {
		var errorLabel = sceGenerate.error || 'Error';
		var networkErrorLabel = sceGenerate.networkError || 'Network error';

		fetch(sceGenerate.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': sceGenerate.restNonce
			},
			body: JSON.stringify(payload)
		})
			.then(function (response) {
				if (!response.ok || !response.body) {
					throw new Error('HTTP ' + response.status);
				}

				var reader = response.body.getReader();
				var decoder = new TextDecoder();
				var buffer = '';
				var currentEvent = 'message';
				var doneHandled = false;

				function parseBlock(block) {
					var eventName = currentEvent;
					var data = {};

					block.split('\n').forEach(function (line) {
						if (line.indexOf('event: ') === 0) {
							eventName = line.slice(7).trim();
						}
						if (line.indexOf('data: ') === 0) {
							try {
								data = JSON.parse(line.slice(6));
							} catch (err) {
								data = {};
							}
						}
					});

					if (eventName === 'error') {
						addError(stepsEl, data.message || errorLabel);
						if (handlers.onError) {
							handlers.onError(data);
						}
						return;
					}

					if (eventName === 'ai_start') {
						addStep(stepsEl, data.message, 'thinking');
						return;
					}

					if (eventName === 'done') {
						doneHandled = true;
						if (handlers.onDone) {
							handlers.onDone(data);
						}
						return;
					}

					if (data.message) {
						addStep(stepsEl, data.message, 'done');
					}
				}

				function pump() {
					return reader.read().then(function (result) {
						if (result.done) {
							if (handlers.onComplete) {
								handlers.onComplete({ doneHandled: doneHandled });
							}
							return;
						}

						buffer += decoder.decode(result.value, { stream: true });
						var chunks = buffer.split('\n\n');
						buffer = chunks.pop() || '';

						chunks.forEach(function (chunk) {
							if (chunk.trim()) {
								parseBlock(chunk);
							}
						});

						return pump();
					});
				}

				return pump();
			})
			.catch(function (err) {
				addError(stepsEl, err.message || networkErrorLabel);
				if (handlers.onError) {
					handlers.onError({ message: err.message });
				}
				if (handlers.onComplete) {
					handlers.onComplete({ doneHandled: false });
				}
			});
	}

	function updateEditForm(definition) {
		var nameField = document.getElementById('sce-name');
		var baseField = document.getElementById('sce-base');
		var categoryField = document.getElementById('sce-category');
		var templateField = document.getElementById('sce-template');
		var stylesField = document.getElementById('sce-styles');
		var scriptsField = document.getElementById('sce-scripts');
		var paramsField = document.getElementById('sce-params-json');
		var editCard = document.getElementById('sce-edit-card');

		if (nameField && definition.name) {
			nameField.value = definition.name;
		}
		if (baseField && definition.base) {
			baseField.value = definition.base;
		}
		if (categoryField && definition.category) {
			categoryField.value = definition.category;
		}
		if (templateField && typeof definition.template === 'string') {
			templateField.value = definition.template;
		}
		if (stylesField && typeof definition.styles === 'string') {
			stylesField.value = definition.styles;
		}
		if (scriptsField && typeof definition.scripts === 'string') {
			scriptsField.value = definition.scripts;
		}
		if (paramsField && definition.params) {
			paramsField.value = JSON.stringify(definition.params, null, 2);
		}

		if (editCard) {
			editCard.classList.add('sce-edit-card--updated');
			window.setTimeout(function () {
				editCard.classList.remove('sce-edit-card--updated');
			}, 1200);
		}
	}

	function addBubble(chatLog, role, text) {
		var bubble = document.createElement('div');
		bubble.className = 'sce-chat-bubble sce-chat-bubble--' + role;
		bubble.textContent = text;
		chatLog.appendChild(bubble);
	}

	function addStep(stepsEl, message, state) {
		var last = stepsEl.querySelector('.sce-chat-step.is-thinking');
		if (last) {
			last.classList.remove('is-thinking');
			last.classList.add('is-done');
		}

		var step = document.createElement('div');
		step.className = 'sce-chat-step';
		if (state === 'thinking') {
			step.classList.add('is-thinking');
		} else if (state === 'done') {
			step.classList.add('is-done');
		}
		step.textContent = message;
		stepsEl.appendChild(step);
	}

	function addError(stepsEl, message) {
		var err = document.createElement('div');
		err.className = 'sce-chat-error';
		err.textContent = message;
		stepsEl.appendChild(err);
	}

	function resetButton(btn, label) {
		btn.disabled = false;
		btn.textContent = label;
	}

	function scrollChatToBottom(chatLog) {
		if (!chatLog) {
			return;
		}
		chatLog.scrollTop = chatLog.scrollHeight;
	}
})();
