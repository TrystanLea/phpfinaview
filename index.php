<?php
    session_start();
    $dir = "/var/lib/phpfina";
    if (isset($_SESSION['last_saved_dir'])) $dir = $_SESSION['last_saved_dir'];
?>

<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script type="text/javascript" src="jquery-1.9.1.min.js"></script>
<link href="style.css" rel="stylesheet">

<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="flot/jquery.js"></script>
<script language="javascript" type="text/javascript" src="flot/jquery.flot.js"></script>
<script language="javascript" type="text/javascript" src="flot/jquery.flot.time.min.js"></script>
<script language="javascript" type="text/javascript" src="flot/jquery.flot.selection.min.js"></script>

<script language="javascript" type="text/javascript" src="vis.helper.js"></script>

<div class="header">
    <input id="dir" type="text" style="width:400px" />
</div>

<div style="  float:left; ">
    <table class="table">
        <tr>
            <th></th>
            <th></th>
            <th></th>
            <th>Val</th>
            <th>Size</th>
            <th>L</th>
            <th>R</th>
        </tr>
        <tbody id="feeds"></tbody>
    </table>
</div>

<div style="width:800px; padding:10px;  float:left; ">
    <div class='btn-group' style="float:left; padding-right:10px">
        <button class='btn time visnav' type='button' time='1'>D</button>
        <button class='btn time' type='button' time='7'>W</button>
        <button class='btn time' type='button' time='30'>M</button>
        <button class='btn time' type='button' time='365'>Y</button>
    </div>
    <div class='btn-group' style="float:left">
        <button id='zoomin' class='btn' >+</button>
        <button id='zoomout' class='btn' >-</button>
        <button id='left' class='btn' ><</button>
        <button id='right' class='btn' >></button>
    </div>
    <div style="clear:both"></div><br>

    <div id="placeholder_bound">
          <div id="placeholder"></div>
    </div>
</div>



<script>

var path = window.location.href;
var dir = "<?php echo $dir; ?>";
$("#dir").val(dir);

var placeholder_bound = $('#placeholder_bound');
var placeholder = $('#placeholder');

var width = placeholder_bound.width();
var height = width * 0.7;

placeholder.width(width);
placeholder_bound.height(height);
placeholder.height(height);

// ---------------------------------------------------------------------------------------
// PHPFina data file list and yaxis display 
// ---------------------------------------------------------------------------------------

var feeds = [
    // {feedid:1, yaxis:1},
];

load_dir(dir);

function load_dir(dir)
{
    feeds = [];
    $.ajax({                                      
        url: path+"api.php?q=scandir&dir="+dir,
        async: false,
        dataType: "json",
        success: function(data) {
            for (z in data) {
                data[z].yaxis = 0;
                feeds.push(data[z]);
            }
            feeds.sort(function(a, b){return a.feedid-b.feedid});
        }
    });

    var out = "";
    for (z in feeds) {
        out += "<tr>";
        out += "<td>"+feeds[z].feedid+".dat</td>";
        out += "<td style='text-align:center; color:#666'>"+feeds[z].interval+"s</td>";
        
        var date = new Date();
        var thisyear = date.getFullYear()-2000;
        
        var date = new Date(feeds[z].start*1000);
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var year = date.getFullYear()-2000;
        var month = months[date.getMonth()];
        var date = date.getDate();
        out += "<td style='text-align:center; color:#666;'>"+date+" "+month;
        if (thisyear!=year) out += " "+year;
        
        out += "&#8594;";
        
        var date = new Date((feeds[z].start+(feeds[z].npoints*feeds[z].interval))*1000);
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var year = date.getFullYear()-2000;
        var month = months[date.getMonth()];
        var date = date.getDate();
        out += date+" "+month;
        if (thisyear!=year) out += " "+year;
        out +="</td>";
                
        out += "<td style='text-align:center; color:#666'>"+list_format_value(feeds[z].lastvalue)+"</td>";
        
        if (feeds[z].size<1024*100) {
            feeds[z].size = (feeds[z].size/1024).toFixed(1)+"kb";
        } else if (feeds[z].size<1024*1024) {
            feeds[z].size = Math.round(feeds[z].size/1024)+"kb";
        } else if (feeds[z].size>=1024*1024) {
            feeds[z].size = Math.round(feeds[z].size/(1024*1024))+"Mb";
        }
        out += "<td style='text-align:center; color:#666'>"+feeds[z].size+"</td>";
        
        out += "<td style='text-align:center'><input class='axischeckbox' feedid="+feeds[z].feedid+" axis=L type='checkbox'/ ></td>";
        out += "<td style='text-align:center'><input class='axischeckbox' feedid="+feeds[z].feedid+" axis=R type='checkbox'/ ></td>";
        out += "</td>";
    }
    $("#feeds").html(out);
    $.plot("#placeholder",[],{});
}

$("#dir").change(function(){
    dir = $(this).val();
    load_dir(dir);
});

$("body").on("click",".axischeckbox",function(){
    var feedid = $(this).attr("feedid");
    var axis = $(this).attr("axis");
    var checked = $(this)[0].checked;
    
    if (checked && axis=='L') $(".axischeckbox[axis=R][feedid="+feedid+"]").prop('checked', false);
    if (checked && axis=='R') $(".axischeckbox[axis=L][feedid="+feedid+"]").prop('checked', false);
    
    for (z in feeds) {
        if (feedid==feeds[z].feedid) {
            if (checked) {
                if (axis=='L') feeds[z].yaxis = 1;
                if (axis=='R') feeds[z].yaxis = 2;
            } else {
                feeds[z].yaxis = 0;
            }
        }
    }
    
    draw();
});

// ---------------------------------------------------------------------------------------
// Graph load, display + navigation code
// ---------------------------------------------------------------------------------------

var timeWindow = 3600*24;
var timenow = (new Date()).getTime();
view.end = timenow;
view.start = view.end - (timeWindow*1000);
var interval = parseInt(timeWindow / 800);
    
draw();

function draw() {

    timeWindow = (view.end - view.start)*0.001;
    var interval = parseInt(timeWindow / 800);
    
    var data = [];
    
    for (z in feeds) 
    {
        if (feeds[z].yaxis!=0)
        {
            var feedid = feeds[z].feedid;
            
            var request = path+"api.php?q=data&dir="+dir+"&id="+feedid+"&start="+view.start+"&end="+view.end+"&interval="+interval+"&skipmissing=1&limitinterval=1";
        
            var options = {
              series: { lines: { show: true } },
              xaxis: { min: view.start, max: view.end, mode: "time", timezone: "browser" },
              selection: { mode: "x" }
            };
        
            $.ajax({                                      
                url: request,
                async: false,
                dataType: "json",
                success: function(data_in) {
                    data.push({label:feedid+".dat", data:data_in,yaxis:feeds[z].yaxis});
                }
            });
        }
    }
    $.plot("#placeholder",data, options);
}

$("#zoomout").click(function () {view.zoomout(); draw();});
$("#zoomin").click(function () {view.zoomin(); draw();});
$('#right').click(function () {view.panright(); draw();});
$('#left').click(function () {view.panleft(); draw();});
$('.time').click(function () {view.timewindow($(this).attr("time")); draw();});

$('#placeholder').bind("plotselected", function (event, ranges) {
    view.start = ranges.xaxis.from;
    view.end = ranges.xaxis.to;
    draw();
});

function list_format_value(value)
{
    if (value>=10) value = (1*value).toFixed(1);
    if (value>=100) value = (1*value).toFixed(0);
    if (value<10) value = (1*value).toFixed(2);
    if (value<=-10) value = (1*value).toFixed(1);
    if (value<=-100) value = (1*value).toFixed(0);
    return value;
}

</script>
