(function($) {
	class cEditorCondition {
		constructor(editor, group, options) {
			this.editor = editor;
			this.group = group;
			this.element = editor.element;

			this.options = $.extend({}, {
				index: null,
				operator: 'equals'
			}, options);

			this._index = this.options.index;
			this._conditionElement = this._createMarkup();

			this._hintElement = this._conditionElement.find(".wam-cleditor__hint");
			this._hintContentElement = this._conditionElement.find(".wam-cleditor__hint-content");

			this._prepareFields(true);
			this._register_events()
		}

		getData() {
			let paramOptions = this._getParamOptions(),
				currentParam = this._conditionElement.find(".wam-cleditor__param-select").val(),
				$operator = this._conditionElement.find(".wam-cleditor__operator-select"),
				currentOperator = $operator.val();

			let value = null;

			if( 'select' === paramOptions['type'] ) {
				value = this._getSelectControlValue(paramOptions);
			} else if( 'equals' === paramOptions['type'] ) {
				value = this._getEqualsControlValue(paramOptions);
			} else if( 'integer' === paramOptions['type'] ) {
				value = this._getIntegerControlValue(paramOptions);
			} else {
				value = this._getTextValue(paramOptions);
			}

			return {
				param: currentParam,
				operator: currentOperator,
				type: paramOptions['type'],
				value: value
			};
		}

		_createMarkup() {
			let conditionTmpl = this.editor.getTemplate(".wam-cleditor__condition");
			this.group.groupElement.find(".wam-cleditor__conditions").append(conditionTmpl);
			return conditionTmpl;
		}

		_remove() {
			this.group.removeCondition(this._index);

			this._conditionElement.remove();

			this.group.groupElement.trigger('winp.conditions-changed');
			this.element.trigger('wam.editor-updated');
		}

		_register_events() {
			let self = this;

			this._conditionElement.find(".wam-cleditor__param-select").change(function() {
				self._prepareFields();
				self.element.trigger('wam.editor-updated');
			});

			this._conditionElement.find(".wam-cleditor__operator-select").change(function() {
				self.element.trigger('wam.editor-updated');
			});

			this._conditionElement.find(".wam-cleditor__condition-value").on('change keyup', function() {
				self.element.trigger('wam.editor-updated');
			})

			// buttons
			this._conditionElement.find(".js-wam-cleditor__condition-remove").click(function() {
				self._remove();
				return false;
			});

			this._conditionElement.find(".js-wam-cleditor__condition-add-and").click(function() {
				self.group.addCondition();
				return false;
			});
		}

		_prepareFields(isInit) {
			if( isInit && this.options.param ) {
				this._selectParam(this.options.param);
			}

			let paramOptions = this._getParamOptions();

			this._setParamHint(paramOptions.description);

			let operators = [];

			if( 'select' === paramOptions['type'] || paramOptions['onlyEquals'] ) {
				operators = ['equals', 'notequal'];
			} else if( 'date' === paramOptions['type'] ) {
				operators = ['equals', 'notequal', 'younger', 'older', 'between'];
			} else if( 'date-between' === paramOptions['type'] ) {
				operators = ['between'];
			} else if( 'integer' === paramOptions['type'] ) {
				operators = ['equals', 'notequal', 'less', 'greater', 'between'];
			} else if( 'equals' === paramOptions['type'] ) {
				operators = ['equals', 'notequal'];
			} else if( 'regexp' === paramOptions['type'] ) {
				operators = ['equals'];
			} else if( 'default' === paramOptions['type'] ) {
				operators = ['equals', 'notequal'];
			} else {
				operators = ['equals', 'notequal', 'contains', 'notcontain'];
			}

			this._setOperators(operators);

			if( isInit && this.options.operator ) {
				this._selectOperator(this.options.operator);
			} else {
				this._selectFirstOperator();
			}

			this._createValueControl(paramOptions, isInit);
		}

		/**
		 * Displays and configures the param hint.
		 */
		_setParamHint(description) {

			if( description ) {
				this._hintContentElement.html(description);
				this._hintElement.show();
			} else {
				this._hintElement.hide();
			}
		}

		/**
		 * Creates control to specify value.
		 */
		_createValueControl(paramOptions, isInit) {
			if( 'select' === paramOptions['type'] ) {
				this._createValueAsSelect(paramOptions, isInit);
			} else if( 'equals' === paramOptions['type'] ) {
				this._createValueAsEquals(paramOptions, isInit);
			} else if( 'integer' === paramOptions['type'] ) {
				this._createValueAsInteger(paramOptions, isInit);
			} else {
				this._createValueAsText(paramOptions, isInit);
			}
		}

		// -------------------
		// Select Control
		// -------------------

		/**
		 * Creates the Select control.
		 */
		_createValueAsSelect(paramOptions, isInit) {
			let self = this;

			let createSelectField = function(values) {
				let $select = self._createSelect(values);
				self._insertValueControl($select);
				if( isInit && self.options.value ) {
					self._setSelectValue(self.options.value);
				}
				self._conditionElement.find(".wam-cleditor__condition-value").trigger("insert.select");
			};

			if( !paramOptions['values'] ) {
				return;
			}
			if( 'ajax' === paramOptions['values']['type'] ) {

				let $fakeSelect = self._createSelect([
					{
						value: null,
						title: '- loading -'
					}
				]);
				self._insertValueControl($fakeSelect);

				$fakeSelect.attr('disabled', 'disabled');
				$fakeSelect.addClass('wam-cleditor__fake-select');

				if( isInit && this.options.value ) {
					$fakeSelect.data('value', this.options.value);
				}

				let req = $.ajax({
					url: window.ajaxurl,
					method: 'post',
					data: {
						action: paramOptions['values']['action']
					},
					dataType: 'json',
					success: function(data) {
						createSelectField(data.values);
					},
					error: function() {
						console.log('Unexpected error during the ajax request.');
					},
					complete: function() {
						if( $fakeSelect ) {
							$fakeSelect.remove();
						}
						$fakeSelect = null;
					}
				});
			} else {
				createSelectField(paramOptions['values']);
			}
		}

		/**
		 * Returns a value for the select control.
		 */
		_getSelectControlValue() {
			let $select = this._conditionElement.find(".wam-cleditor__condition-value select");

			let value = $select.val();
			if( !value ) {
				value = $select.data('value');
			}
			return value;
		}

		/**
		 * Sets a select value.
		 */
		_setSelectValue(value) {
			let $select = this._conditionElement.find(".wam-cleditor__condition-value select");

			if( $select.hasClass('.wam-cleditor__fake-select') ) {
				$select.data('value', value);
			} else {
				$select.val(value);
			}
		}

		// -------------------
		// Integer Control
		// -------------------

		/**
		 * Creates a control for the input linked with the integer.
		 */
		_createValueAsInteger(paramOptions, isInit) {
			let self = this;

			let $operator = this._conditionElement.find(".wam-cleditor__operator-select");

			$operator.on('change', function() {
				let currentOperator = $operator.val();

				let $control;
				if( 'between' === currentOperator ) {
					$control = $("<span><input type='text' class='wam-cleditor__integer-start' /> and <input type='text' class='wam-cleditor__integer-end' /></span>");
				} else {
					$control = $("<input type='text' class='wam-cleditor__integer-solo' /></span>");
				}

				self._insertValueControl($control);
			});

			$operator.change();
			if( isInit && this.options.value ) {
				this._setIntegerValue(this.options.value);
			}
		}

		/**
		 * Returns a value for the Integer control.
		 */
		_getIntegerControlValue() {
			let value = {};

			let $operator = this._conditionElement.find(".wam-cleditor__operator-select");
			let currentOperator = $operator.val();

			if( 'between' === currentOperator ) {
				value.range = true;
				value.start = this._conditionElement.find(".wam-cleditor__integer-start").val();
				value.end = this._conditionElement.find(".wam-cleditor__integer-end").val();

			} else {
				value = this._conditionElement.find(".wam-cleditor__integer-solo").val();
			}

			return value;
		}

		/**
		 * Sets a value for the Integer control.
		 */
		_setIntegerValue(value) {
			if( !value ) {
				value = {};
			}

			if( value.range ) {
				this._conditionElement.find(".wam-cleditor__integer-start").val(value.start);
				this._conditionElement.find(".wam-cleditor__integer-end").val(value.end);
			} else {
				this._conditionElement.find(".wam-cleditor__integer-solo").val(value);
			}
		}

		// -------------------
		// Query string Control
		// -------------------

		/**
		 * Creates a control for the input linked with the integer.
		 */
		_createValueAsEquals(paramOptions, isInit) {
			let self = this;

			let $operator = this._conditionElement.find(".wam-cleditor__operator-select");
			let $control;

			$control = $("<span><input type='text' class='wam-cleditor__equals-value1' /> <span class='wam-cleditor__equals-icon'>=</span> <input type='text' class='wam-cleditor__equals-value2' /></span>");

			if( paramOptions['placeholder'] && $.isArray(paramOptions['placeholder']) ) {
				$control.find('.wam-cleditor__equals-value1').attr('placeholder', paramOptions['placeholder'][0]);

				if( paramOptions['placeholder'][1] ) {
					$control.find('.wam-cleditor__equals-value2').attr('placeholder', paramOptions['placeholder'][1]);
				}
			}

			self._insertValueControl($control);

			$operator.on('change', function() {
				let currentOperator = $operator.val();
				let equalIcon = $control.find('.wam-cleditor__equals-icon');

				if( 'equals' === currentOperator ) {
					equalIcon.text('=');
				} else {
					equalIcon.text('≠');
				}
			});

			$operator.change();

			if( isInit && this.options.value ) {
				this._setEqualsControlValue(this.options.value);
			}
		}

		/**
		 * Returns a value for the Integer control.
		 */
		_getEqualsControlValue() {
			let value = {};

			value.var_name = this._conditionElement.find(".wam-cleditor__equals-value1").val();
			value.var_value = this._conditionElement.find(".wam-cleditor__equals-value2").val();

			return value;
		}

		/**
		 * Sets a value for the Integer control.
		 */
		_setEqualsControlValue(value) {
			if( !value ) {
				value = {};
			}

			this._conditionElement.find(".wam-cleditor__equals-value1").val(value.var_name);
			this._conditionElement.find(".wam-cleditor__equals-value2").val(value.var_value);
		}

		// -------------------
		// Text Control
		// -------------------

		/**
		 * Creates a control for the input linked with the integer.
		 */
		_createValueAsText(paramOptions, isInit) {
			let $control = $("<input type='text' class='wam-cleditor__text' /></span>");

			if( paramOptions['placeholder'] ) {
				$control.attr('placeholder', paramOptions['placeholder']);
			}

			this._insertValueControl($control);

			if( isInit && this.options.value && "" !== this.options.value ) {
				this._setTextValue(this.options.value);
			} else if( paramOptions['default_value'] ) {
				this._setTextValue(paramOptions['default_value'])
			}
		}

		/**
		 * Returns a value for the Text control.
		 * @returns {undefined}
		 */
		_getTextValue() {
			return this._conditionElement.find(".wam-cleditor__text").val();
		}

		/**
		 * Sets a value for the Text control.
		 */
		_setTextValue(value) {
			this._conditionElement.find(".wam-cleditor__text").val(value);
		}

		// -------------------
		// Helper Methods
		// -------------------

		_selectParam(value) {
			this._conditionElement.find(".wam-cleditor__param-select").val(value);
		}

		_selectOperator(value) {
			this._conditionElement.find(".wam-cleditor__operator-select").val(value);
		}

		_selectFirstOperator() {
			this._conditionElement.find(".wam-cleditor__operator-select").prop('selectedIndex', 0);
		}

		_setOperators(values) {
			let $operator = this._conditionElement.find(".wam-cleditor__operator-select");
			$operator.show();//.off('change');

			$operator.find("option").hide();
			for( let index in values ) {
				if( !values.hasOwnProperty(index) ) {
					continue;
				}
				$operator.find("option[value='" + values[index] + "']").show();
			}
			let value = $operator.find("option:not(:hidden):eq(0)").val();
			$operator.val(value);
		}

		_insertValueControl($control) {
			this._conditionElement.find(".wam-cleditor__condition-value").html("").append($control);
		}

		_getParamOptions() {
			let selectElement = this._conditionElement.find(".wam-cleditor__param-select"),
				optionElement = selectElement.find('option:selected');

			if( !selectElement.length ) {
				return false;
			}

			let type = optionElement.data('type'),
				data = {
					id: selectElement.val(),
					title: optionElement.text().trim(),
					type: optionElement.data('type'),
					default_value: optionElement.data('default-value'),
					values: optionElement.data('params'),
					description: optionElement.data('hint').trim()
				};

			if( "text" === type || "default" === type || "regexp" === type || "equals" === type ) {
				data['placeholder'] = optionElement.data('placeholder');
				delete data['values'];
			}

			return data;
		}

		_createSelect(values, attrs) {
			let $select = $("<select></select>");
			if( attrs ) {
				$select.attr(attrs);
			}

			for( let index in values ) {
				if( !values.hasOwnProperty(index) ) {
					continue;
				}
				let item = values[index];
				let $option = '';

				if( typeof index === "string" && isNaN(index) === true ) {
					let $optgroup = $("<optgroup></optgroup>").attr('label', index);

					for( let subindex in item ) {
						if( !item.hasOwnProperty(subindex) ) {
							continue;
						}
						let subvalue = item[subindex];
						$option = $("<option></option>").attr('value', subvalue['value']).text(subvalue['title']);
						$optgroup.append($option);
					}
					$select.append($optgroup);
				} else {
					$option = $("<option></option>").attr('value', item['value']).text(item['title']);
					$select.append($option);
				}
			}

			return $select;
		}
	}

	class cEditorGroup {
		constructor(editor, options) {
			this.editor = editor;
			this.element = editor.element;

			this.options = $.extend({}, {
				conditions: null,
				index: null
			}, options);
			this._index = this.options.index;

			this.conditions = {};

			this.groupElement = this._createMarkup();

			this._conditionsCounter = 0;

			this._load();
		}

		getData() {
			let condtions = [];

			for( let ID in this.conditions ) {
				if( !this.conditions.hasOwnProperty(ID) ) {
					continue;
				}

				condtions.push(this.conditions[ID].getData());
			}

			if( !condtions.length ) {
				return null;
			}

			return {
				type: 'OR',
				conditions: condtions
			};
		}

		getCountConditions() {
			return Object.keys(this.conditions).length;
		}

		removeCondition(ID) {
			if( this.conditions[ID] ) {
				delete this.conditions[ID];
			}
		}

		_createMarkup() {
			let $group = this.editor.getTemplate('.wam-cleditor__group');
			this.element.find(".wam-cleditor__groups").append($group);

			if( this._index <= 1 ) {
				$group.find('.wam-cleditor__group-type').hide();
				$group.find('.js-wam-cleditor__remove-group').remove();
			} else {
				$group.find('.wam-cleditor__group-type').show();
				$group.find('.wam-cleditor__first-group-title').remove();
			}

			return $group;
		}

		_registerEvents() {
			let self = this;

			this.groupElement.find(".js-wam-cleditor__add-condition").click(function() {
				self.addCondition();
				return false;
			});

			this.groupElement.find(".js-wam-cleditor__remove-group").click(function() {
				self._remove();
				return false;
			});

			this.groupElement.on('winp.conditions-changed', function() {
				self._checkIsEmpty();
			});
		}

		_load() {
			if( !this.options.conditions ) {
				this.addCondition();
			} else {
				this._setGroupData();
			}

			this._registerEvents();
		}

		_remove() {
			this.editor.removeGroup(this._index);
			this.groupElement.remove();

			this.element.trigger('wam.filters-changed');
			this.element.trigger('wam.editor-updated');
		}

		_setGroupData() {
			this.groupElement.find('.wam-cleditor__condition').remove();

			if( this.options.conditions ) {
				for( let index in this.options.conditions ) {
					if( !this.options.conditions.hasOwnProperty(index) ) {
						continue;
					}

					this.addCondition(this.options.conditions[index]);
				}
			}

			this._checkIsEmpty();
		}

		addCondition(data) {
			if( !data ) {
				data = {type: 'AND'};
			}

			this._conditionsCounter = this._conditionsCounter + 1;
			data.index = this._index + '_' + this._conditionsCounter;

			this.conditions[data.index] = new cEditorCondition(this.editor, this, data);

			this.groupElement.trigger('winp.conditions-changed');
			this.element.trigger('wam.editor-updated');
		}

		_checkIsEmpty() {
			if( this.getCountConditions() === 0 ) {
				this.groupElement.addClass('wam-cleditor__empty');
			} else {
				this.groupElement.removeClass('wam-cleditor__empty');
			}
		}
	}

	class cEditor {
		constructor(element, options) {
			this.element = element;

			this.options = $.extend({}, {
				groups: null,
				// where to get an editor template
				templateSelector: null,
				// where to put editor options
				saveInputSelector: null,
				callback: null
			}, options);

			this.groups = {};
			this.groupsCounter = 0;

			this.element = this._createMarkup();

			this._load();

			if( this.options.callback ) {
				this.options.callback(this);
			}
		}

		/*showParams() {
			this.element.find('.wam-cleditor__param-select').find('options').show();
		}

		hideParams(params) {
			if( params.length ) {
				for( let i = 0; i < params.length; i++ ) {
					this.element.find('.wam-cleditor__param-select').find('option[value="' + params[i] + '"]').hide();
				}
			}
		}*/

		getData() {
			let self = this;
			let groups = [];

			for( let ID in self.groups ) {
				if( !self.groups.hasOwnProperty(ID) ) {
					continue;
				}

				let groupData = self.groups[ID].getData();

				if( groupData ) {
					groups.push(self.groups[ID].getData());
				}
			}

			if( !groups.length ) {
				return null;
			}

			return groups;
		}

		getImportData() {
			if( this.options.saveInputSelector ) {
				let data = this.element.parent().find(this.options.saveInputSelector).val();

				if( !data ) {
					return null;
				}

				return JSON.parse(data);
			}

			return null;
		}

		setExportData() {
			if( this.options.saveInputSelector && $(this.options.saveInputSelector).length ) {
				let data = !this.getData() ? '' : JSON.stringify(this.getData());
				this.element.parent().find(this.options.saveInputSelector).val(data);
			} else {
				throw new Error('[Error]: Save input is not found! Selector: ' + this.options.saveInputSelector);
			}
		}

		getTemplate(selector) {
			let tmpl = $($(this.options.templateSelector).html());

			if( !tmpl.length ) {
				throw new Error('[Error]: Editor template is not found! Selector: ' + this.options.templateSelector);
			}

			return tmpl.find(selector).clone();
		}

		getCountGroups() {
			return Object.keys(this.groups).length;
		}

		removeGroup(ID) {
			if( this.groups[ID] ) {
				delete this.groups[ID];
			}
		}

		destroy() {
			this.element.remove();
		}

		_registerEvents() {
			let self = this;

			this.element.on('wam.editor-updated', function() {
				self.setExportData();
			});

			this.element.on('wam.filters-changed', function() {
				self._checkIsEmpty();
			});

			this.element.find(".js-wam-cleditor__add-group").click(function() {
				self._addGroup();
				return false;
			});
		}

		_createMarkup() {
			let $editor = $('<div></div>').addClass('wam-cleditor');
			this.element.prepend($editor);

			$editor.append(this.getTemplate('.wam-cleditor__wrap'));
			$editor.append(this.getTemplate('.wam-cleditor__buttons-group'));

			return $editor;
		}

		_load() {
			let groups, savedOptions;

			savedOptions = this.getImportData();

			if( savedOptions ) {
				groups = savedOptions;
			} else if( this.options.groups && this.options.groups.length > 0 ) {
				groups = this.options.groups;
			}

			if( groups ) {
				for( let index in groups ) {
					if( !groups.hasOwnProperty(index) ) {
						continue;
					}

					this._addGroup(groups[index]);
				}
			}

			this._checkIsEmpty();
			this._registerEvents();

			// If editor will create demo data, we will trigger an event
			if( !savedOptions ) {
				this.element.trigger('wam.editor-updated');
			}

		}

		_addGroup(data) {
			if( !data ) {
				data = {type: 'OR'};
			}

			this.groupsCounter = this.groupsCounter + 1;

			this.groups[this.groupsCounter] = new cEditorGroup(this, {
				index: this.groupsCounter,
				conditions: data.conditions
			});

			this.element.trigger('wam.editor-updated');
			this.element.trigger('wam.filters-changed');
		}

		_checkIsEmpty() {
			if( this.getCountGroups() === 0 ) {
				this.element.addClass('wam-cleditor__empty');
			} else {
				this.element.removeClass('wam-cleditor__empty');
			}
		}
	}

	$.fn.wamConditionsEditor = function(options) {
		return this.each(function() {
			new cEditor($(this), options);
		});
	};

})(jQuery);