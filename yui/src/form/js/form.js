/**
 * JavaScript for form editing auth conditions.
 *
 * @module moodle-availability_auth-form
 */
M.availability_auth = M.availability_auth || {};

// Class M.availability_auth.form @extends M.core_availability.plugin.
M.availability_auth.form = Y.Object(M.core_availability.plugin);

// Authentication methods available for selection.
M.availability_auth.form.auths = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} auths Array of objects containing authid => name
 */
M.availability_auth.form.initInner = function(auths) {
    this.auths = auths;
};

M.availability_auth.form.getNode = function(json) {
    // Create HTML structure.
    var tit = M.util.get_string('title', 'availability_auth');
    var html = '<label class="form-group"><span class="p-r-1">' + tit + '</span>';
    html += '<span class="availability-auth"><select class="custom-select" name="id" title=' + tit + '>';
    html += '<option value="choose">' + M.util.get_string('choosedots', 'moodle') + '</option>';
    for (var i = 0; i < this.auths.length; i++) {
        var auth = this.auths[i];
        html += '<option value="' + auth.id + '">' + auth.name + '</option>';
    }
    html += '</select></span></label>';
    var node = Y.Node.create('<span class="form-inline">' + html + '</span>');

    // Set initial values (leave default 'choose' if creating afresh).
    if (json.creating === undefined) {
        if (json.id !== undefined && node.one('select[name=id] > option[value=' + json.id + ']')) {
            node.one('select[name=id]').set('value', json.id);
        } else if (json.id === undefined) {
            node.one('select[name=id]').set('value', 'choose');
        }
    }

    // Add event handlers (first time only).
    if (!M.availability_auth.form.addedEvents) {
        M.availability_auth.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            // Just update the form fields.
            M.core_availability.form.update();
        }, '.availability_auth select');
    }

    return node;
};

M.availability_auth.form.focusAfterAdd = function(node) {
    var selected = node.one('select[name=id]').get('value');
    if (selected === 'choose') {
        // Make default hidden if no value chosen.
        var eyenode = node.ancestor().one('.availability-eye');
        eyenode.simulate('click');
    }
    var target = node.one('input:not([disabled]),select:not([disabled])');
    target.focus();
};

M.availability_auth.form.fillValue = function(value, node) {
    var selected = node.one('select[name=id]').get('value');
    if (selected === 'choose') {
        value.id = '';
    } else {
        value.id = selected;
    }
};

M.availability_auth.form.fillErrors = function(errors, node) {
    var selected = node.one('select[name=id]').get('value');
    if (selected === 'choose') {
        errors.push('availability_auth:missing');
    }
};
