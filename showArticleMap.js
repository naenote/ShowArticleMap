var dataset = JSON.parse(eval("jQuery('#show-article-map-dataset').text()"));
var nodedata = dataset[0];
var edgedata = dataset[1];
var nodes = new vis.DataSet(nodedata);
var edges = new vis.DataSet(edgedata);
var container = document.getElementById('mynetwork');
var data = { nodes: nodes, edges: edges };
var options = {
    nodes: { shape: "box" },
    edges: { arrows: { to: { enabled: true, scaleFactor: 1, type: 'arrow' } } },
    manipulation: { enabled: true },
};
var network = new vis.Network(container, data, options);

network.on('doubleClick', function(e) {
    var nodeID = e.nodes.toString();
    var url = jQuery(data.nodes.get(nodeID).title).attr('href');
    window.open(url, '_blank');
});

jQuery('#searchnodebutton').on('click', function() {
    var search = jQuery('#searchnodequery').val();

    // serch nodes by node label
    var hitNodes = nodes.get({
        filter: function(item) {
            var label = item.label.replace("\\r\\n", "");
            return label.indexOf(search) != -1;
        }
    });
    var hitNodeIDs = [];
    for (i = 0; i < hitNodes.length; i++) {
        hitNodeIDs.push(hitNodes[i].id);
    };

    // select
    network.selectNodes(hitNodeIDs);
});
jQuery('#searchnodequery').keypress(function(e) {
    if (e.which == 13) { //Enter key pressed
        jQuery('#searchnodebutton').click(); //Trigger search button click event
    }
});

//initialize group list
var groupList = nodes.distinct('group').sort();
for (var i = 0; i < groupList.length; i++) {
    jQuery('#groupList').append('<input type="checkbox" name="visibleGroups" value="' + groupList[i] + '" checked="checked" style="margin-left:15px;">' + groupList[i]);
}

// prepare node data by group
var nodeGroups = [];
for (var i = 0; i < groupList.length; i++) {
    nodeGroups[groupList[i]] = nodes.get({ filter: function(item) { return item.group == groupList[i]; } });
}

// apply group change
jQuery('#groupList>input').on('change', function() {
    var currentGroupNames = nodes.distinct('group');
    var visibleGroupNames = [];
    jQuery("#groupList :checkbox:checked").each(function() {
        visibleGroupNames.push(this.value);
    });
    var diffGroupNames = diffArray(currentGroupNames, visibleGroupNames);
    if (currentGroupNames.length < visibleGroupNames.length) {
        for (i = 0; i < diffGroupNames.length; i++) {
            nodes.add(nodeGroups[diffGroupNames[i]]);
        }
    }
    else if (currentGroupNames.length > visibleGroupNames.length) {
        for (i = 0; i < diffGroupNames.length; i++) {
            nodes.remove(nodeGroups[diffGroupNames[i]]);
        }
    }
    else {

    }
});

function diffArray(arr1, arr2) {
    return arr1.concat(arr2).filter(item => !arr1.includes(item) || !arr2.includes(item));
}

// toggle physics
jQuery('#togglepBlur').on('click', function() {
    var physicsEnabled = network.physics.options.enabled;
    var buttonText = physicsEnabled ? "Start" : "Stop";
    network.setOptions({ physics: { enabled: !physicsEnabled } });
    jQuery(this).text(buttonText);
});
