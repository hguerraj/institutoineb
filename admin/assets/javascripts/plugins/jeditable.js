/*
 * Jeditable - jQuery in place edit plugin
 *
 * CUSTOMIZED FOR CRUD GENERATOR - Don't update or be careful !
 *
 * Copyright (c) 2006-2009 Mika Tuupola, Dylan Verheul
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   http://www.appelsiini.net/projects/jeditable
 *
 * Based on editable by Dylan Verheul <dylan_at_dyve.net>:
 *    http://www.dyve.net/jquery/?editable
 *
 */

/**
 * Version 1.7.1
 *
 * ** means there is basic unit tests for this parameter.
 *
 * @name  Jeditable
 * @type  jQuery
 * @param String  target             (POST) URL or function to send edited content to **
 * @param Hash    options            additional options
 * @param String  options[method]    method to use to send edited content (POST or PUT) **
 * @param Function options[callback] Function to run after submitting edited content **
 * @param String  options[name]      POST parameter name of edited content
 * @param String  options[id]        POST parameter name of edited div id
 * @param Hash    options[submitdata] Extra parameters to send when submitting edited content.
 * @param String  options[type]      text, textarea or select (or any 3rd party input type) **
 * @param Integer options[rows]      number of rows if using textarea **
 * @param Integer options[cols]      number of columns if using textarea **
 * @param Mixed   options[height]    'auto', 'none' or height in pixels **
 * @param Mixed   options[width]     'auto', 'none' or width in pixels **
 * @param String  options[loadurl]   URL to fetch input content before editing **
 * @param String  options[loadtype]  Request type for load url. Should be GET or POST.
 * @param String  options[loadtext]  Text to display while loading external content.
 * @param Mixed   options[loaddata]  Extra parameters to pass when fetching content before editing.
 * @param Mixed   options[data]      Or content given as paramameter. String or function.**
 * @param String  options[indicator] indicator html to show when saving
 * @param String  options[tooltip]   optional tooltip text via title attribute **
 * @param String  options[event]     jQuery event such as 'click' of 'dblclick' **
 * @param String  options[submit]    submit button value, empty means no button **
 * @param String  options[cancel]    cancel button value, empty means no button **
 * @param String  options[cssclass]  CSS class to apply to input form. 'inherit' to copy from parent. **
 * @param String  options[style]     Style to apply to input form 'inherit' to copy from parent. **
 * @param String  options[select]    true or false, when true text is highlighted ??
 * @param String  options[placeholder] Placeholder text or html to insert when element is empty. **
 * @param String  options[onblur]    'cancel', 'submit', 'ignore' or function ??
 *
 * @param Function options[onsubmit] function(settings, original) { ... } called before submit
 * @param Function options[onreset]  function(settings, original) { ... } called before reset
 * @param Function options[onerror]  function(settings, original, xhr) { ... } called on error
 *
 * @param Hash    options[ajaxoptions]  jQuery Ajax options. See docs.jquery.com.
 *
 */

 (function ($) {
    $.fn.editable = function (target, options) {
        if ('disable' == target) {
            $(this).data('disabled.editable', true);
            return;
        }
        if ('enable' == target) {
            $(this).data('disabled.editable', false);
            return;
        }
        if ('destroy' == target) {
            $(this)
                .unbind($(this).data('event.editable'))
                .removeData('disabled.editable')
                .removeData('event.editable');
            return;
        }

        const settings = $.extend({}, $.fn.editable.defaults, { target: target }, options);

        /* setup some functions */
        const plugin = $.editable.types[settings.type].plugin || function () { };
        const submit = $.editable.types[settings.type].submit || function () { };
        const buttons = $.editable.types[settings.type].buttons || $.editable.types['defaults'].buttons;
        const content = $.editable.types[settings.type].content || $.editable.types['defaults'].content;
        const element = $.editable.types[settings.type].element || $.editable.types['defaults'].element;
        const reset = $.editable.types[settings.type].reset || $.editable.types['defaults'].reset;
        const callback = settings.callback || function () { };
        const onedit = settings.onedit || function () { };
        const onsubmit = settings.onsubmit || function () { };
        const onreset = settings.onreset || function () { };
        const onerror = settings.onerror || reset;

        /* show tooltip */
        if (settings.tooltip) {
            $(this).attr('title', settings.tooltip);
        }

        settings.autowidth = 'none' == settings.width;
        settings.autoheight = 'none' == settings.height;

        return this.each(function () {
            /* save this to self because this changes when scope changes */
            const self = this;

            /* inlined block elements lose their width and height after first edit */
            /* save them for later use as workaround */
            const savedwidth = $(self).width();
            const savedheight = $(self).height();

            /* save so it can be later used by $.editable('destroy') */
            $(this).data('event.editable', settings.event);

            /* if element is empty add something clickable (if requested) */
            if (!$.trim($(this).html())) {
                $(this).html(settings.placeholder);
            }

            $(this).bind(settings.event, function (e) {
                /* abort if disabled for this element */
                if (true === $(this).data('disabled.editable')) {
                    return;
                }

                /* prevent throwing an exeption if edit field is clicked again */
                if (self.editing) {
                    return;
                }

                $(e.target).attr({ 'data-value': e.target.innerText, 'data-loading': true });

                /* abort if onedit hook returns false */
                if (false === onedit.apply(this, [settings, self])) {
                    return;
                }

                /* prevent default action and bubbling */
                e.preventDefault();
                e.stopPropagation();

                /* remove tooltip */
                if (settings.tooltip) {
                    $(self).removeAttr('title');
                }

                /* figure out how wide and tall we are, saved width and height */
                /* are workaround for http://dev.jquery.com/ticket/2190 */
                if (0 == $(self).width()) {
                    //$(self).css('visibility', 'hidden');
                    settings.width = savedwidth;
                    settings.height = savedheight;
                } else {
                    if (settings.width != 'none') {
                        settings.width = settings.autowidth ? $(self).width() : settings.width;
                    }
                    if (settings.height != 'none') {
                        settings.height = settings.autoheight ? $(self).height() : settings.height;
                    }
                }
                //$(this).css('visibility', '');

                /* remove placeholder text, replace is here because of IE */
                if (
                    $(this)
                        .html()
                        .toLowerCase()
                        .replace(/(;|")/g, '') == settings.placeholder.toLowerCase().replace(/(;|")/g, '')
                ) {
                    $(this).html('');
                }

                self.editing = true;
                self.revert = $(self).html();
                $(self).html('');

                setTimeout(() => {
                    loadJeditableForm(e);
                }, 0);
            });

            const loadJeditableForm = (e) => {
                $(e.target).removeAttr('data-loading');

                /* create the form object */
                const form = $('<form />');

                /* apply css or style or both */
                if (settings.cssclass) {
                    if ('inherit' == settings.cssclass) {
                        form.attr('class', $(self).attr('class'));
                    } else {
                        form.attr('class', settings.cssclass);
                    }
                }

                if (settings.style) {
                    if ('inherit' == settings.style) {
                        form.attr('style', $(self).attr('style'));
                        /* IE needs the second line or display wont be inherited */
                        form.css('display', $(self).css('display'));
                    } else {
                        form.attr('style', settings.style);
                    }
                }

                /* add main input element to form and store it in input */
                const input = element.apply(form, [settings, self]);

                /* set input content via POST, GET, given data or existing value */
                let input_content;

                let t;

                if (settings.loadurl) {
                    t = setTimeout(function () {
                        input.disabled = true;
                        content.apply(form, [settings.loadtext, settings, self]);
                    }, 100);

                    const loaddata = {};
                    loaddata[settings.id] = self.id;
                    if ($.isFunction(settings.loaddata)) {
                        $.extend(loaddata, settings.loaddata.apply(self, [self.revert, settings]));
                    } else {
                        $.extend(loaddata, settings.loaddata);
                    }
                    $.ajax({
                        type: settings.loadtype,
                        url: settings.loadurl,
                        data: loaddata,
                        async: false,
                        success: function (result) {
                            window.clearTimeout(t);
                            input_content = result;
                            input.disabled = false;
                        }
                    });
                } else if (settings.data) {
                    input_content = settings.data;
                    if ($.isFunction(settings.data)) {
                        input_content = settings.data.apply(self, [self.revert, settings]);
                    }
                } else {
                    input_content = self.revert;
                }
                content.apply(form, [input_content, settings, self]);

                input.attr('name', settings.name);

                /* add buttons to the form */
                buttons.apply(form, [settings, self]);

                /* add created form to self */
                $(self).append(form);

                /* attach 3rd party plugin if requested */
                plugin.apply(form, [settings, self]);

                /* focus to first visible form element */
                $(':input:visible:enabled:first', form).focus();

                /* highlight input contents when requested */
                if (settings.select) {
                    input.select();
                }

                /* discard changes if pressing esc */
                input.keydown(function () {
                    if (e.keyCode == 27) {
                        e.preventDefault();
                        //self.reset();
                        reset.apply(form, [settings, self]);
                    }
                });

                /* discard, submit or nothing with changes when clicking outside */
                /* do nothing is usable when navigating with tab */
                if ('cancel' == settings.onblur) {
                    input.blur(function () {
                        /* prevent canceling if submit was clicked */
                        t = setTimeout(function () {
                            reset.apply(form, [settings, self]);
                        }, 500);
                    });
                } else if ('submit' == settings.onblur) {
                    input.blur(function () {
                        /* prevent double submit if submit was clicked */
                        t = setTimeout(function () {
                            form.submit();
                        }, 200);
                    });
                } else if ($.isFunction(settings.onblur)) {
                    input.blur(function () {
                        settings.onblur.apply(self, [input.val(), settings]);
                    });
                } else {
                    input.blur(function () {
                        /* TODO: maybe something here */
                    });
                }

                form.submit(function () {
                    if (t) {
                        clearTimeout(t);
                    }

                    /* do no submit */
                    e.preventDefault();

                    /* call before submit hook. */
                    /* if it returns false abort submitting */
                    if (false !== onsubmit.apply(form, [settings, self])) {
                        /* custom inputs call before submit hook. */
                        /* if it returns false abort submitting */
                        if (false !== submit.apply(form, [settings, self])) {
                            /* check if given target is function */
                            if ($.isFunction(settings.target)) {
                                const str = settings.target.apply(self, [input.val(), settings]);
                                $(self).html(str);
                                self.editing = false;
                                callback.apply(self, [self.innerHTML, settings]);
                                /* TODO: this is not dry */
                                if (!$.trim($(self).html())) {
                                    $(self).html(settings.placeholder);
                                }
                            } else {
                                /* add edited content and id of edited element to POST */
                                const submitdata = {};
                                submitdata[settings.name] = input.val();
                                submitdata[settings.id] = self.id;
                                /* add extra data to be POST:ed */
                                if ($.isFunction(settings.submitdata)) {
                                    $.extend(submitdata, settings.submitdata.apply(self, [self.revert, settings]));
                                } else {
                                    $.extend(submitdata, settings.submitdata);
                                }

                                /* quick and dirty PUT support */
                                if ('PUT' == settings.method) {
                                    submitdata['_method'] = 'put';
                                }

                                /* show the saving indicator */
                                $(self).html(settings.indicator);

                                /* defaults for ajaxoptions */
                                const ajaxoptions = {
                                    type: 'POST',
                                    data: submitdata,
                                    dataType: 'html',
                                    url: settings.target,
                                    success: function (result, _status) {
                                        if (ajaxoptions.dataType == 'html') {
                                            $(self).html(result);
                                        }
                                        self.editing = false;
                                        callback.apply(self, [result, settings]);
                                        if (!$.trim($(self).html())) {
                                            $(self).html(settings.placeholder);
                                        }
                                    },
                                    error: function (xhr, _status, _error) {
                                        onerror.apply(form, [settings, self, xhr]);
                                    }
                                };

                                /* override with what is given in settings.ajaxoptions */
                                $.extend(ajaxoptions, settings.ajaxoptions);
                                $.ajax(ajaxoptions);
                            }
                        }
                    }

                    /* show tooltip again */
                    $(self).attr('title', settings.tooltip);

                    return false;
                });
            };

            /* privileged methods */
            this.reset = function (form) {
                /* prevent calling reset twice when blurring */
                if (this.editing) {
                    /* before reset hook, if it returns false abort reseting */
                    if (false !== onreset.apply(form, [settings, self])) {
                        $(self).html(self.revert);
                        self.editing = false;
                        if (!$.trim($(self).html())) {
                            $(self).html(settings.placeholder);
                        }
                        /* show tooltip again */
                        if (settings.tooltip) {
                            $(self).attr('title', settings.tooltip);
                        }
                    }
                }
            };
        });
    };

    $.editable = {
        types: {
            defaults: {
                element: function (_settings, _original) {
                    const input = $('<input type="hidden"></input>');
                    $(this).append(input);
                    return input;
                },
                content: function (string, _settings, _original) {
                    $(':input:first', this).val(string);
                },
                reset: function (_settings, original) {
                    original.reset(this);
                },
                buttons: function (settings, original) {
                    const form = this;

                    // group btns
                    let btnWrapper = $('<div></div>');
                    if (settings.submit && settings.cancel) {
                        btnWrapper = $('<div class="btn-group"></div>');
                    }
                    $(this).append(btnWrapper);
                    if (settings.cancel) {
                        /* if given html string use that */
                        let cancel;
                        if (settings.cancel.match(/>$/)) {
                            cancel = $(settings.cancel);
                            /* otherwise use button with given string as text */
                        } else {
                            cancel = $('<button type="cancel" class="btn btn-warning btn-xs mt-2" />');
                            cancel.html(settings.cancel);
                        }
                        $(btnWrapper).append(cancel);

                        $(cancel).click(function (_event) {
                            //original.reset();
                            let reset;
                            if ($.isFunction($.editable.types[settings.type].reset)) {
                                reset = $.editable.types[settings.type].reset;
                            } else {
                                reset = $.editable.types['defaults'].reset;
                            }
                            reset.apply(form, [settings, original]);
                            return false;
                        });
                    }
                    if (settings.submit) {
                        /* if given html string use that */
                        let submit;
                        if (settings.submit.match(/>$/)) {
                            submit = $(settings.submit).click(function () {
                                if (submit.attr('type') != 'submit') {
                                    form.submit();
                                }
                            });
                            /* otherwise use button with given string as text */
                        } else {
                            submit = $('<button type="submit" class="btn btn-primary btn-xs mt-2" />');
                            submit.html(settings.submit);
                        }
                        $(btnWrapper).append(submit);
                    }
                }
            },
            text: {
                element: function (_settings, _original) {
                    const input = $('<input />').attr('class', 'form-control');
                    // if (settings.width  != 'none') { input.width(settings.width);  }
                    // if (settings.height != 'none') { input.height(settings.height); }
                    /* https://bugzilla.mozilla.org/show_bug.cgi?id=236791 */
                    //input[0].setAttribute('autocomplete','off');
                    input.attr('autocomplete', 'off');
                    $(this).append(input);
                    return input;
                }
            },
            textarea: {
                element: function (settings, _original) {
                    const textarea = $('<textarea />');
                    if (settings.rows) {
                        textarea.attr('rows', settings.rows);
                    } else if (settings.height != 'none') {
                        textarea.height(settings.height);
                    }
                    if (settings.cols) {
                        textarea.attr('cols', settings.cols);
                    } else if (settings.width != 'none') {
                        textarea.width(settings.width);
                    }
                    $(this).append(textarea);
                    return textarea;
                }
            },
            select: {
                element: function (_settings, _original) {
                    const select = $('<select />');
                    $(this).append(select);
                    return select;
                },
                content: function (data, _settings, original) {
                    let json;
                    /* If it is string assume it is json. */
                    if (String == data.constructor) {
                        eval('json = ' + data);
                    } else {
                        /* Otherwise assume it is a hash already. */
                        json = data;
                    }
                    for (const key in json) {
                        if (!json.hasOwnProperty(key)) {
                            continue;
                        }
                        if ('selected' == key) {
                            continue;
                        }
                        const option = $('<option />')
                            .val(key)
                            .append(json[key]);
                        $('select', this).append(option);
                    }
                    /* For PHPCG, select the value grabbed from the data-value set in vendor/twig/twig/src/Extension/CrudTwigExtension.php */
                    if ($(original) !== undefined && $(original).attr('data-value') != undefined) {
                        json.selected = $(original).attr('data-value');
                    }
                    /* Loop option again to set selected. IE needed this... */
                    $('select', this)
                        .children()
                        .each(function () {
                            if ($(this).val() == json['selected'] || $(this).text() == $.trim(original.revert)) {
                                $(this).attr('selected', 'selected');
                            }
                        });
                },
                plugin: function (b, c) {
                    const e = this,
                        a = e.find('select');
                    let showSearch = e.find('select option').length > 10 ? true : false;
                    b.onblur = 'nothing';
                    new SlimSelect({
                        select: document.querySelector('select[name="' + $(a).attr('name') + '"]'),
                        showSearch: showSearch
                    });
                }
            }
        },

        /* Add new input type */
        addInputType: function (name, input) {
            $.editable.types[name] = input;
        }
    };

    // publicly accessible defaults
    $.fn.editable.defaults = {
        name: 'value',
        id: 'id',
        type: 'text',
        width: 'auto',
        height: 'auto',
        event: 'click.editable',
        onblur: 'cancel',
        loadtype: 'GET',
        loadtext: 'Loading...',
        placeholder: 'Click to edit',
        loaddata: {},
        submitdata: {},
        ajaxoptions: {}
    };
})(jQuery);
$.editable.addInputType('datepicker', {
    element: function (_b, _c) {
        const e = $(this),
            a = $('<input />');
        a.attr('autocomplete', 'off');
        e.append(a);
        return a;
    },
    plugin: function (b, c) {
        const e = this,
            a = e.find('input');
        b.onblur = 'nothing';
        const datepicker = {
            dateFormat: 'yy-mm-dd',
            onSelect: function () {
                e.submit();
            },
            onClose: function () {
                setTimeout(function () {
                    a.is(':focus') ? e.submit() : c.reset(e);
                }, 150);
            }
        };
        b.datepicker && jQuery.extend(datepicker, b.datepicker);
        a.datepicker(datepicker);
    }
});
$.editable.addInputType('pickadate', {
    element: function (_b, c) {
        const e = $(this),
            a = $('<input />');
        if ($(c).attr('data-value') != 'undefined') {
            a.attr('data-value', $(c).attr('data-value'));
        }
        a.attr('autocomplete', 'off');
        e.append(a);
        return a;
    },
    plugin: function (b, c) {
        const e = this,
            a = e.find('input');
        b.onblur = 'nothing';
        const pickadate = {
            formatSubmit: 'yyyy-mm-dd',
            onOpen: function () {
                // mCSB = custom scroll bar plugin
                if ($('.mCSB_container')[0]) {
                    $('.mCSB_container').css('overflow', 'visible');
                }
            },
            onSet: function (z) {
                // if clear button
                if (typeof z.clear == 'object' && z.clear == null) {
                    $(a).val('');
                    e.submit();
                }
                // if the clicked element is a date
                if (typeof z.select == 'number') {
                    e.submit();
                }
            },
            onClose: function () {
                if ($('.mCSB_container')[0]) {
                    $('.mCSB_container').css('overflow', 'hidden');
                }
                setTimeout(function () {
                    a.is(':focus') ? e.submit() : c.reset(e);
                }, 150);
            }
        };
        b.pickadate && jQuery.extend(pickadate, b.pickadate);
        a.pickadate(pickadate);
    }
});
