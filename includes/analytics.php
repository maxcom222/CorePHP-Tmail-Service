<?php defined("APP") or die() ?>
/**
 * Custom Analytics - Premium URL Shortener
 * Version <?php echo _VERSION ?>
 */
// Generate Data
var countries ={<?php foreach($country as $c => $click): ?>"<?php echo $c ?>":<?php echo $click ?>,<?php endforeach ?>}
var data =  [{ data: [<?php foreach($clicks as $time => $click): ?>[<?php echo strtotime($time)*1000?>,<?php echo $click ?>],<?php endforeach ?>] }]

var options = {
  series: {
  //lines: { show: true, lineWidth: 2,fill: true},                
  //points: { show: true, lineWidth: 2 }, 
  bars: { show: true, barWidth: 1000*60*60*24, align: 'center' },
  shadowSize: 0
  },
  grid: { hoverable: true, clickable: true, tickColor: 'transparent', borderWidth:0 },
  colors: ['#0da1f5', '#1ABC9C','#F11010'],
  yaxis: {ticks:3, tickDecimals: 0, color: '#CFD2E0'},
  xaxes: [ { mode: 'time', timeformat: "%d %b"} ]
} 

$.plot("#url-chart",data, options);
// fetch one series, adding to what we got
var alreadyFetched = {};

$(".chart_data").click(function (e) {
  e.preventDefault();  
  var id = $(this).attr("data-value");
  $(".chart_data").removeClass("active");
  $(this).addClass("active");  

  if(id=="d"){
    options.series.bars.barWidth= 1000*60*60*24;
    options.xaxes[0].timeformat= "%d %b";
    return $.plot("#url-chart", data, options);
  }
  
  $.ajax({
      type: "POST",
      url: appurl+"/server",
      data: "request=chart&id="+id+"&token="+token,
      dataType: 'json',
      success: function(series){
        if(id[2]=="m"){
          options.series.bars.barWidth= 1000*60*60*24*31;
          options.xaxes[0].timeformat= "%b %Y";
        }else if(id[2]=="y"){
          options.series.bars.barWidth= 1000*60*60*24*365;
          options.xaxes[0].timeformat= "%Y";
        }
        $.plot("#url-chart", [series], options);
      }
  });
});
$('#country').vectorMap({
  map: 'world_mill_en',
  backgroundColor: 'transparent',
  series: {
    regions: [{
      values: countries,
      scale: ['#C8EEFF', '#0071A4'],
      normalizeFunction: 'polynomial'
    }]
  },
  onRegionLabelShow: function(e, el, code){
    if(typeof countries[code]!="undefined") el.html(el.html()+' ('+countries[code]+' Clicks)');
  }     
});
// Append Country List
<?php foreach($top_country as $c=>$click): ?>
$("#country-list").append('<li><?php echo $c ?> <small>(<?php echo round(($click/$total*100),1) ?>%)</small><span class="label label-primary pull-right"><?php echo $click?></span></li>');
<?php endforeach ?> 
// Append Referrer 
<?php foreach($referrers as $r=>$click): ?>
$("#referrer").append('<li><?php echo $r ?> <small>(<?php echo round(($click/$total*100),1) ?>%)</small><span class="label label-primary pull-right"><?php echo $click?></span></li>');
<?php endforeach ?>    
// Append Social Cicks
<?php if(!$fb && !$tw && !$gl): ?>
  $("#social-shares").animate({height: "40px"}).html('<p style="color: #bbb;padding-top: 10px;text-align: center;font-weight: 700;">No clicks from social media.</p>');
<?php else: ?>
var social = [
    {data: <?php echo $fb ?>, color: '#3B5998',label: "Facebook (<?php echo $fb ?>)"},
    {data: <?php echo $tw ?>, color: '#409DD5',label: "Twitter  (<?php echo $tw ?>)"},
    {data: <?php echo $gl ?>, color: '#D34836',label: "Google Plus (<?php echo $gl ?>)"}
];
$.plot($("#social-shares"), social,
{
series: {
  pie: { 
    show: true,
    radius: 1,
    label: {
      show: true,
      radius: 2/3,
      formatter: function(label, series){
      console.log(series);
        return '<div style="font-size:8pt;text-align:center;padding:2px;color:white;">'+label+'<br/>'+Math.round(series.percent)+'%</div>';
      },
      threshold: 0.1
    }
  }},legend: {show: false },grid: { hoverable: true}});
<?php endif; ?>  
// Browsers
var browsers = [
<?php foreach($browsers as $r): ?>
  <?php if(empty($r->browser)) continue; ?>
    {
        value: <?php echo $r->count ?>,
        label: "<?php echo $r->browser ?>",
        color: "<?php echo color($r->browser) ?>",
    },    
<?php endforeach ?>     
];
var ctx = $("#browsers").get(0).getContext("2d")
  var myDoughnutChart = new Chart(ctx).Doughnut(browsers,{
    responsive: true
  });
 //then you just need to generate the legend
  var legend = myDoughnutChart.generateLegend();

  //and append it to your page somewhere
  $('#browsers').after(legend);  
  // os
  var os = [
  <?php foreach($os as $r): ?>
    <?php if(empty($r->os)) continue; ?>
      {
          value: <?php echo $r->count ?>,
          label: "<?php echo $r->os ?>",
          color: "<?php echo os($r->os) ?>"
      },    
  <?php endforeach ?>     
  ]
  var ctx = $("#os").get(0).getContext("2d")
  var myDoughnutChart = new Chart(ctx).Doughnut(os,{
    responsive: true
  });  
 //then you just need to generate the legend
  var legend = myDoughnutChart.generateLegend();

  //and append it to your page somewhere
  $('#os').after(legend);    
<?php 
  function color($b){
      $a = array(
                'Internet Explorer' => '#2cbeff',
                'Firefox' => '#e75800',
                'Safari' => '#3290c7',
                'Chrome' => '#ffc62a',
                'Opera' => '#e01349',
                'Netscape' => '#106964',
                'Maxthon' => '#3da2f2',
                'Konqueror' => '#033add',
                'Handheld Browser' => '#008000',
                'Unknown Browser' => '#000000',
                '' => "#eee"
            );
    return $a[$b];
  }
 function os($b){
      $a = array(
          'Windows 10' => '#4200FF',
          'Windows 8.1' => '#4FA7FF',
          'Windows 8' => '#0F87FF',
          'Windows 7' => '#0080C0',
          'Windows Vista' => '#80FFFF',
          'Windows Server 2003/XP x64' => '#FF0000',
          'Windows XP' => '#FF0000',
          'Windows 2000' => '#C0C0C0',
          'Windows ME' => '#C0C0C0',
          'Windows 98' => '#C0C0C0',
          'Windows 95' => '#C0C0C0',
          'Windows 3.11' => '#C0C0C0',
          'Mac' => '#000',
          'Mac OS X' => '#000',
          'Mac OS 9' => '#000',
          'Linux' => '#F1DF03',
          'Ubuntu' => '#d40000',
          'iPhone' => '#4D4D4D',
          'iPod' => '#464646',
          'iPad' => '#7A7A7A',
          'Android' => '#a4c639',
          'BlackBerry' => '#0080FF',
          'Mobile' => '#FF0080',
          'Unknown OS' => "#eee"
      );
    return $a[$b];
  }  
function random_color_part() {
    return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
}

function random_color() {
    return random_color_part() . random_color_part() . random_color_part();
}     
 ?>