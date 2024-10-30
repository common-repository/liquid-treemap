<?php
/*
Author: LIQUID DESIGN Ltd.
*/

// is_admin
if ( !is_admin() ) {
    $home = home_url();
    header("Location: {$home}");
}
// get
if(!empty($_GET["range"])){
    $range = htmlspecialchars($_GET["range"]);
}else{
    $range = 'all';
}
//wpp
if (function_exists('wpp_get_mostpopular')) {
    $wpp = 1;
}
?>
<div class="wrap liquid_treemap <?php echo $range; ?>">
<h1>LIQUID TREEMAP</h1>
<!-- tab -->
<h2 class="nav-tab-wrapper">
<?php if (function_exists('liquid_analytics')) { ?>
<a href="?page=liquid_analytics" class="nav-tab">Content Analytics</a>
<?php } ?>
<a href="?page=liquid_treemap" class="nav-tab <?php if(empty($_GET["tab"])){ ?>nav-tab-active<?php } ?>">TreeMap</a>
<?php if( !empty($json_liquid_treemap->recommend) ){ ?>
<a href="?page=liquid_treemap&tab=recommend" class="nav-tab <?php if(!empty($_GET["tab"])){ ?>nav-tab-active<?php } ?>">Recommend</a>
<?php } ?>
</h2>

<?php if(empty($_GET["tab"])){ ?>
<!-- main -->
<?php if(empty($wpp)){ ?>
<p>Comments</p>
<?php }else{ ?>
<ul class="subsubsub">
    <li>Value: </li>
    <li><a href="?page=liquid_treemap" class="all">Pageview</a></li>
    <li><a href="?page=liquid_treemap&mode=comments" class="daily">Comments</a></li>
</ul>
<ul class="subsubsub">
    <li>Pageview Range: </li>
    <li><a href="?page=liquid_treemap" class="all">All</a></li>
    <li><a href="?page=liquid_treemap&range=daily" class="daily">daily</a></li>
    <li><a href="?page=liquid_treemap&range=weekly" class="weekly">weekly</a></li>
    <li><a href="?page=liquid_treemap&range=monthly" class="monthly">monthly</a></li>
</ul>
<?php } ?>
<hr style="clear: both;">
<?php

//main
$json_post = '{ "name": "all", "children": [';
$categories = get_categories();
//$categories = get_categories('parent=0');
$ci = 0; $ci_length = count($categories);
foreach($categories as $category) {
    $args = array( 'posts_per_page' => 300, 'post_type' => 'any', 'category_name' => $category->slug );//order=ASC
    $posts = get_posts( $args );
    $json_post .= '{ "name": "'.esc_html($category->name).'", "children": [';
    $pi = 0; $pi_length = count($posts);
    foreach ( $posts as $post ) {
        setup_postdata( $post );
        if(empty($wpp) || !empty($_GET["mode"]) && $_GET["mode"] == 'comments'){
            $comments = wp_count_comments($post->ID);
            $number = $comments->total_comments;
        }else{
            //pageviews
            if(!empty($_GET["range"]) && $_GET["range"] == 'daily'){
                $number = wpp_get_views($post->ID, 'daily', true);
            }elseif(!empty($_GET["range"]) && $_GET["range"] == 'weekly'){
                $number = wpp_get_views($post->ID, 'weekly', true);
            }elseif(!empty($_GET["range"]) && $_GET["range"] == 'monthly'){
                $number = wpp_get_views($post->ID, 'monthly', true);
            }else{
                $number = wpp_get_views($post->ID, 'all', true);
            }
        }
        $number = str_replace(',', '', $number);
        if(empty($postfix[$post->ID])){
            $json_post .= '{"name": "'.esc_html($post->post_title).'", "value": '.$number.'}';
            $postfix[$post->ID] = 1;
        }else{
            $json_post .= '{"name": "'.esc_html($post->post_title).'", "value": 0}';
        }
        $pi++;
        if($pi !== $pi_length){
            $json_post .= ',';
        }
    }
    $ci++;
    $json_post .= ']}';
    if($ci !== $ci_length){
        $json_post .= ',';
    }
    wp_reset_postdata();
}
$json_post .= ']};';
?>

<div id="chart"></div>

<script src="<?php echo plugins_url( 'js/d3.js', __FILE__ ); ?>"></script>
<script>
var margin = {top: 20, right: 0, bottom: 0, left: 0},
    width = 960,
    height = 500 - margin.top - margin.bottom,
    formatNumber = d3.format(",d"),
    transitioning;

var color = d3.scale.category20b();

var x = d3.scale.linear()
    .domain([0, width])
    .range([0, width]);

var y = d3.scale.linear()
    .domain([0, height])
    .range([0, height]);

var treemap = d3.layout.treemap()
    .children(function(d, depth) { return depth ? null : d._children; })
    .sort(function(a, b) { return a.value - b.value; })
    .ratio(height / width * 0.5 * (1 + Math.sqrt(5)))
    .round(false);

var svg = d3.select("#chart").append("svg")
    .attr("width", width + margin.left + margin.right)
    .attr("height", height + margin.bottom + margin.top)
    .style("margin-left", -margin.left + "px")
    .style("margin.right", -margin.right + "px")
  .append("g")
    .attr("transform", "translate(" + margin.left + "," + margin.top + ")")
    .style("shape-rendering", "crispEdges");

var grandparent = svg.append("g")
    .attr("class", "grandparent");

grandparent.append("rect")
    .attr("y", -margin.top)
    .attr("width", width)
    .attr("height", margin.top);

grandparent.append("text")
    .attr("x", 6)
    .attr("y", 6 - margin.top)
    .attr("dy", ".75em");

var root = <?php echo $json_post; ?>
  initialize(root);
  accumulate(root);
  layout(root);
  display(root);

  function initialize(root) {
    root.x = root.y = 0;
    root.dx = width;
    root.dy = height;
    root.depth = 0;
  }

  function accumulate(d) {
    return (d._children = d.children)
        ? d.value = d.children.reduce(function(p, v) { return p + accumulate(v); }, 0)
        : d.value;
  }

  function layout(d) {
    if (d._children) {
      treemap.nodes({_children: d._children});
      d._children.forEach(function(c) {
        c.x = d.x + c.x * d.dx;
        c.y = d.y + c.y * d.dy;
        c.dx *= d.dx;
        c.dy *= d.dy;
        c.parent = d;
        layout(c);
      });
    }
  }

  function display(d) {
    grandparent
        .datum(d.parent)
        .on("click", transition)
      .select("text")
        .text(name(d));

    var g1 = svg.insert("g", ".grandparent")
        .datum(d)
        .attr("class", "depth");

    var g = g1.selectAll("g")
        .data(d._children)
      .enter().append("g");

    g.filter(function(d) { return d._children; })
        .classed("children", true)
        .on("click", transition);

    g.selectAll(".child")
        .data(function(d) { return d._children || [d]; })
      .enter().append("rect")
        .attr("class", "child")
        .call(rect);

    g.append("rect")
        .attr("class", "parent")
        .call(rect)
        .style("fill", function(d) { return color(d.value); })
      .append("title")
        .text(function(d) { return d.name + " : " + formatNumber(d.value); });

    g.append("text")
        .attr("dy", ".75em")
        .text(function(d) { return formatNumber(d.value); })
        .call(text);

    function transition(d) {
      if (transitioning || !d) return;
      transitioning = true;

      var g2 = display(d),
          t1 = g1.transition().duration(750),
          t2 = g2.transition().duration(750);

      // Update the domain only after entering new elements.
      x.domain([d.x, d.x + d.dx]);
      y.domain([d.y, d.y + d.dy]);

      // Enable anti-aliasing during the transition.
      svg.style("shape-rendering", null);

      // Draw child nodes on top of parent nodes.
      svg.selectAll(".depth").sort(function(a, b) { return a.depth - b.depth; });

      // Fade-in entering text.
      g2.selectAll("text").style("fill-opacity", 1);

      // Transition to the new view.
      t1.selectAll("text").call(text).style("fill-opacity", 1);
      t2.selectAll("text").call(text).style("fill-opacity", 1);
      t1.selectAll("rect").call(rect);
      t2.selectAll("rect").call(rect);

      // Remove the old node when the transition is finished.
      t1.remove().each("end", function() {
        svg.style("shape-rendering", "crispEdges");
        transitioning = false;
      });
    }

    return g;
  }

  function text(text) {
    text.attr("x", function(d) { return x(d.x) + 6; })
        .attr("y", function(d) { return y(d.y) + 6; });
  }

  function rect(rect) {
    rect.attr("x", function(d) { return x(d.x); })
        .attr("y", function(d) { return y(d.y); })
        .attr("width", function(d) { return x(d.x + d.dx) - x(d.x); })
        .attr("height", function(d) { return y(d.y + d.dy) - y(d.y); });
  }

  function name(d) {
    return d.parent
        ? name(d.parent) + "." + d.name
        : d.name;
  }
</script>

<div id="help">
<?php if(empty($wpp)){ ?>
<p><b>* Required for pageview: "<a href="https://ja.wordpress.org/plugins/wordpress-popular-posts/" target="_blank">WordPress Popular Posts</a>" Plugin.</b></p>
<?php } ?>
</div>

<?php
// recommend
}elseif( $_GET["tab"] == 'recommend' ){
    if( !empty($json_liquid_treemap->recommend) ){
        echo '<div style="padding:10px; background: #fff;">'.$json_liquid_treemap->recommend.'</div>';
    }
}
?>
<hr><a href="https://lqd.jp/wp/" target="_blank">LIQUID PRESS</a>
</div>