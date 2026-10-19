<?php
/*
Template Name: Careers
*/

// Load this client's public ATS feed while keeping the existing career-page design.
$burke_ats_url = 'https://assistmyhr.com/jobs-widget/1?version=3d4e90c323a09cef&workspace=the-burke-group-py3hyy7yle&signature=f19e951bd6a932601f46c982d099bffa6449cbc81873e0cc861034e1188755aa';
$burke_ats_page = isset($_GET['ats_jobs_page']) && is_scalar($_GET['ats_jobs_page'])
    ? max(1, min(10000, absint(wp_unslash($_GET['ats_jobs_page'])))) : 1;
$burke_ats_cache_key = 'burke_ats_jobs_' . md5($burke_ats_url . ':' . $burke_ats_page);
$burke_ats_feed = get_transient($burke_ats_cache_key);

if ($burke_ats_feed === false) {
    $burke_ats_response = wp_remote_get(add_query_arg('page', $burke_ats_page, $burke_ats_url), array(
        'headers' => array('Accept' => 'application/json'),
        'timeout' => 10,
        'redirection' => 0,
        'limit_response_size' => 2097152,
    ));
    $burke_ats_feed = array('unavailable' => true);
    if (!is_wp_error($burke_ats_response) && wp_remote_retrieve_response_code($burke_ats_response) === 200) {
        $burke_ats_decoded = json_decode(wp_remote_retrieve_body($burke_ats_response), true);
        if (is_array($burke_ats_decoded) && ($burke_ats_decoded['status'] ?? false) === true
            && isset($burke_ats_decoded['jobs']) && is_array($burke_ats_decoded['jobs'])
            && isset($burke_ats_decoded['pagination']) && is_array($burke_ats_decoded['pagination'])) {
            $burke_ats_feed = $burke_ats_decoded;
        }
    }
    // Briefly cache errors; never keep old job listings after a failed refresh.
    set_transient($burke_ats_cache_key, $burke_ats_feed, empty($burke_ats_feed['unavailable']) ? MINUTE_IN_SECONDS : 15);
}

get_header();
?>


  <style>
a.cta_button{-moz-box-sizing:content-box !important;-webkit-box-sizing:content-box !important;box-sizing:content-box !important;vertical-align:middle}.hs-breadcrumb-menu{list-style-type:none;margin:0px 0px 0px 0px;padding:0px 0px 0px 0px}.hs-breadcrumb-menu-item{float:left;padding:10px 0px 10px 10px}.hs-breadcrumb-menu-divider:before{content:'›';padding-left:10px}.hs-featured-image-link{border:0}.hs-featured-image{float:right;margin:0 0 20px 20px;max-width:50%}@media (max-width: 568px){.hs-featured-image{float:none;margin:0;width:100%;max-width:100%}}.hs-screen-reader-text{clip:rect(1px, 1px, 1px, 1px);height:1px;overflow:hidden;position:absolute !important;width:1px}
</style> 
  <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/hubfs/hub_generated/template_assets/1/65416061990/1742183465437/template_main.css"> 
  <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/hubfs/hub_generated/module_assets/1/65416061953/1742300499084/module_menu-section.min.css"> 
  <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/hubfs/hub_generated/module_assets/1/65416061833/1742300484761/module_button.min.css"> 
  <style>
  #hs_cos_wrapper_widget_1648828144712 .jobs__files-item {
  background:black;
  display:flex;
  align-items:center;
  flex-wrap:wrap;
  justify-content:space-between;
  padding:37px 49px 25px 78px;
  border:1px black solid;
}

#hs_cos_wrapper_widget_1648828144712 .jobs__files-item:nth-child(even) {
  background:#262626;
  display:flex;
  align-items:center;
  flex-wrap:wrap;
  justify-content:space-between;
  padding:37px 49px 25px 78px;
  border:1px black solid;
}

#hs_cos_wrapper_widget_1648828144712 .jobs__title { width:30%; }

#hs_cos_wrapper_widget_1648828144712 .jobs__title .title {
  font-size:28px;
  font-weight:bold;
  color:#ffffff;
  margin-bottom:10px;
  line-height:1.5;
}

#hs_cos_wrapper_widget_1648828144712 .jobs__title .date {
  font-size:16px;
  color:#ffffff;
}

#hs_cos_wrapper_widget_1648828144712 .jobs__conditions { width:20%; }

#hs_cos_wrapper_widget_1648828144712 .jobs__conditions p {
  margin-bottom:13px;
  font-size:18px;
  color:#ffffff;
}

#hs_cos_wrapper_widget_1648828144712 .jobs__button {
  background-color:#9e856c;
  border:none;
  text-align:center;
  display:flex;
  font-size:16px;
  margin:4px 2px;
  cursor:pointer;
  height:42px;
  align-items:center;
  justify-content:center;
  border:1px solid #9e856c;
}

#hs_cos_wrapper_widget_1648828144712 .jobs__button a {
  text-decoration:none;
  color:black;
  font-size:15px;
  font-family:"Playfair Display";
  padding:11px 32px;
  line-height:20px;
}

#hs_cos_wrapper_widget_1648828144712 .jobs__button:hover,
#hs_cos_wrapper_widget_1648828144712 .jobs__button a:hover {
  background-color:transparent;
  color:white;
}

#hs_cos_wrapper_widget_1648828144712 .jobs__ubications {
  display:flex;
  align-items:center;
  width:10%;
  justify-content:flex-start;
}

#hs_cos_wrapper_widget_1648828144712 .jobs__ubications img {
  width:21px;
  filter:brightness(0) saturate(100%) invert(80%) sepia(13%) saturate(769%) hue-rotate(356deg) brightness(86%) contrast(90%);
  margin-right:22px;
}

#hs_cos_wrapper_widget_1648828144712 .jobs__ubications p {
  margin-bottom:0px;
  font-size:18px;
  color:#ffffff;
}

#hs_cos_wrapper_widget_1648828144712 ::-webkit-scrollbar {
  box-shadow:inset 0 0 5px grey;
  border-radius:10px;
}

#hs_cos_wrapper_widget_1648828144712 ::-webkit-scrollbar-thumb {
  background:#9e856c;
  border-radius:10px;
  box-shadow:inset 0 0 5px black;
}

@media (max-width:768px) {
  #hs_cos_wrapper_widget_1648828144712 .jobs__files-item,
  #hs_cos_wrapper_widget_1648828144712 .jobs__files-item:nth-child(even) {
    padding:37px 20px 25px 20px;
    display:grid;
    justify-items:center;
    justify-content:center;
  }

  #hs_cos_wrapper_widget_1648828144712 .jobs__title,
  #hs_cos_wrapper_widget_1648828144712 .jobs__conditions,
  #hs_cos_wrapper_widget_1648828144712 .jobs__ubications {
    width:100%;
    text-align:center;
  }

  #hs_cos_wrapper_widget_1648828144712 .jobs__button { margin-top:20px; }

  #hs_cos_wrapper_widget_1648828144712 .jobs__ubications img { margin-right:12px; }

  #hs_cos_wrapper_widget_1648828144712 .jobs__ubications { justify-content:center; }
}


#hs_cos_wrapper_widget_1648828144712 .jobs__feedback { padding:40px 25px; color:#fff; text-align:center; font-family:"Playfair Display",serif; font-size:20px; line-height:1.6; }
#hs_cos_wrapper_widget_1648828144712 .jobs__feedback a { color:#c9ad85; }
#hs_cos_wrapper_widget_1648828144712 .jobs__pagination { display:flex; justify-content:center; align-items:center; flex-wrap:wrap; gap:24px; padding:30px 20px; color:#fff; font-family:"Playfair Display",serif; }
#hs_cos_wrapper_widget_1648828144712 .jobs__pagination a { color:#c9ad85; text-decoration:none; }
#hs_cos_wrapper_widget_1648828144712 .jobs__pagination a:hover { color:#fff; }
#hs_cos_wrapper_widget_1648828144712 .jobs__files-item { gap:18px; }
#hs_cos_wrapper_widget_1648828144712 .jobs__title, #hs_cos_wrapper_widget_1648828144712 .jobs__conditions, #hs_cos_wrapper_widget_1648828144712 .jobs__ubications { min-width:0; overflow-wrap:anywhere; }

.closing-cta {
  background:#000000;
  padding:90px 7% 100px;
}

.closing-cta__wrap {
  max-width:1180px;
  margin:0 auto;
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:70px;
}

.closing-cta__block {
  color:#ffffff;
}

.closing-cta__block h2 {
  color:#ffffff;
  font-family:"Playfair Display";
  font-size:34px;
  line-height:1.18;
  letter-spacing:0.02em;
  margin:0 0 25px;
  text-transform:uppercase;
}

.closing-cta__block p {
  color:#ffffff;
  font-family:"Playfair Display";
  font-size:20px;
  line-height:1.55;
  margin:0 0 22px;
}

.closing-cta__block em {
  color:#c9ad85;
  display:block;
  font-family:"Playfair Display";
  font-size:22px;
  line-height:1.4;
  margin:4px 0 26px;
}

.closing-cta__link {
  color:#c9ad85;
  display:inline-block;
  font-family:"Playfair Display";
  font-size:18px;
  font-weight:700;
  letter-spacing:0.02em;
  text-decoration:none;
  text-transform:uppercase;
}

.closing-cta__link:hover {
  color:#ffffff;
}

@media (max-width:768px) {
  .closing-cta {
    padding:60px 25px 70px;
  }

  .closing-cta__wrap {
    grid-template-columns:1fr;
    gap:45px;
  }

  .closing-cta__block h2 {
    font-size:28px;
  }

  .closing-cta__block p {
    font-size:18px;
  }
}

</style> 
  <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/hubfs/hub_generated/module_assets/1/65415636583/1742300468158/module_featured-image.min.css"> 
  <style>
  #widget_1645050098951 img {
    border-radius: 0px;
    opacity: 1.0;
  }
</style> 
  <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/hubfs/hub_generated/module_assets/1/65416061979/1742300507591/module_social-follow.min.css"> 
  <style>
  #hs_cos_wrapper_widget_1645051600009 .social-links__icon {
    background-color: rgba(40, 42, 43,0.0);
    border-radius: 0px;
    border-color: rgba(40, 42, 43,1.0);
    padding: 10px;
  }
  #hs_cos_wrapper_widget_1645051600009 .social-links__link {
    margin-right: 5px;
  }
  #hs_cos_wrapper_widget_1645051600009 .social-links__link:last-child {
    margin-right: 0;
  }
  #hs_cos_wrapper_widget_1645051600009 .social-links__icon svg {
    fill: #c9ad85;
    height: 31px;
    width: 31px;
  }
  #hs_cos_wrapper_widget_1645051600009 .social-links__icon:hover,
  #hs_cos_wrapper_widget_1645051600009 .social-links__icon:focus,
  #hs_cos_wrapper_widget_1645051600009 .social-links__icon:active {
    background-color: rgba(40, 42, 43,0.0);
  }
  #hs_cos_wrapper_widget_1645051600009 .social-links__icon:hover svg,
  #hs_cos_wrapper_widget_1645051600009 .social-links__icon:focus svg,
  #hs_cos_wrapper_widget_1645051600009 .social-links__icon:active svg {
    fill: #b09774;
  }
</style> 
  <style>
    @media(max-width: 768px){
     .footer_drag_drop-row-0-vertical-alignment > .row-fluid {
    display: -ms-flexbox !important;
    -ms-flex-direction: row;
    display: flex !important;
    flex-direction: row;
    flex-wrap: wrap;
    justify-content: space-evenly;
	}
}
  
  @media(max-width: 426px){
    .footer_drag_drop-row-0-vertical-alignment > .row-fluid{
      display: grid !important;
    }
  }
</style> 
  <style>
  @font-face {
    font-family: "Playfair Display";
    font-weight: 400;
    font-style: normal;
    font-display: swap;
    src: url("/_hcms/googlefonts/Playfair_Display/regular.woff2") format("woff2"), url("/_hcms/googlefonts/Playfair_Display/regular.woff") format("woff");
  }
  @font-face {
    font-family: "Playfair Display";
    font-weight: 700;
    font-style: normal;
    font-display: swap;
    src: url("/_hcms/googlefonts/Playfair_Display/700.woff2") format("woff2"), url("/_hcms/googlefonts/Playfair_Display/700.woff") format("woff");
  }
  @font-face {
    font-family: "Open Sans";
    font-weight: 400;
    font-style: normal;
    font-display: swap;
    src: url("/_hcms/googlefonts/Open_Sans/regular.woff2") format("woff2"), url("/_hcms/googlefonts/Open_Sans/regular.woff") format("woff");
  }
  @font-face {
    font-family: "Open Sans";
    font-weight: 700;
    font-style: normal;
    font-display: swap;
    src: url("/_hcms/googlefonts/Open_Sans/700.woff2") format("woff2"), url("/_hcms/googlefonts/Open_Sans/700.woff") format("woff");
  }
</style> 
  <!-- Editor Styles --> 
  <style id="hs_editor_style" type="text/css">
#hs_cos_wrapper_widget_1649080736589  { display: block !important; padding-bottom: 49px !important }
#hs_cos_wrapper_widget_1649080736589  { display: block !important; padding-bottom: 49px !important }
.footer_drag_drop-row-0-max-width-section-centering > .row-fluid {
  max-width: 1420px !important;
  margin-left: auto !important;
  margin-right: auto !important;
}
/* HubSpot Non-stacked Media Query Styles */
@media (min-width:768px) {
  .footer_drag_drop-row-0-vertical-alignment > .row-fluid {
    display: -ms-flexbox !important;
    -ms-flex-direction: row;
    display: flex !important;
    flex-direction: row;
  }
  .footer_drag_drop-column-1-vertical-alignment {
    display: -ms-flexbox !important;
    -ms-flex-direction: column !important;
    -ms-flex-pack: center !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
  }
  .footer_drag_drop-column-1-vertical-alignment > div {
    flex-shrink: 0 !important;
  }
  .cell_1645051600011-vertical-alignment {
    display: -ms-flexbox !important;
    -ms-flex-direction: column !important;
    -ms-flex-pack: center !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
  }
  .cell_1645051600011-vertical-alignment > div {
    flex-shrink: 0 !important;
  }
  .cell_1645051219804-vertical-alignment {
    display: -ms-flexbox !important;
    -ms-flex-direction: column !important;
    -ms-flex-pack: center !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
  }
  .cell_1645051219804-vertical-alignment > div {
    flex-shrink: 0 !important;
  }
  .cell_1645050883041-vertical-alignment {
    display: -ms-flexbox !important;
    -ms-flex-direction: column !important;
    -ms-flex-pack: center !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
  }
  .cell_1645050883041-vertical-alignment > div {
    flex-shrink: 0 !important;
  }
  .cell_1645050050182-vertical-alignment {
    display: -ms-flexbox !important;
    -ms-flex-direction: column !important;
    -ms-flex-pack: center !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
  }
  .cell_1645050050182-vertical-alignment > div {
    flex-shrink: 0 !important;
  }
}
/* HubSpot Styles (default) */
.dnd_area-row-0-margin {
  margin-top: -160px !important;
}
.dnd_area-row-0-padding {
  padding-top: 200px !important;
  padding-bottom: 0px !important;
}
.dnd_area-row-0-background-layers {
  background-image: linear-gradient(rgba(0, 0, 0, 1), rgba(0, 0, 0, 1)) !important;
  background-position: left top !important;
  background-size: auto !important;
  background-repeat: no-repeat !important;
}
.dnd_area-row-1-padding {
  padding-top: 0px !important;
  padding-bottom: 0px !important;
}
.dnd_area-row-1-background-layers {
  background-image: linear-gradient(rgba(0, 0, 0, 1), rgba(0, 0, 0, 1)) !important;
  background-position: left top !important;
  background-size: auto !important;
  background-repeat: no-repeat !important;
}
.dnd_area-row-2-padding {
  padding-top: 100px !important;
  padding-bottom: 0px !important;
}
.dnd_area-row-2-background-layers {
  background-image: linear-gradient(rgba(0, 0, 0, 1), rgba(0, 0, 0, 1)) !important;
  background-position: left top !important;
  background-size: auto !important;
  background-repeat: no-repeat !important;
}
.dnd_area-row-3-background-layers {
  background-image: url('<?php echo get_template_directory_uri(); ?>/hubfs/Burke%20request%20a%20consult-06.png') !important;
  background-position: center top !important;
  background-size: cover !important;
  background-repeat: no-repeat !important;
}
.footer_drag_drop-row-0-padding {
  padding-top: 55px !important;
  padding-bottom: 25px !important;
  padding-left: px !important;
  padding-right: px !important;
}
.cell_1645051600011-padding {
  padding-top: 0px !important;
  padding-bottom: 0px !important;
  padding-left: 0px !important;
  padding-right: 0px !important;
}
.cell_1645051219804-padding {
  padding-top: 0px !important;
  padding-bottom: 0px !important;
  padding-left: 0px !important;
  padding-right: 0px !important;
}
.cell_1645050883041-padding {
  padding-top: px !important;
  padding-bottom: px !important;
  padding-left: 0px !important;
  padding-right: 0px !important;
}
.cell_1645050050182-margin {
  margin-top: -10px !important;
  margin-bottom: 30px !important;
}
.cell_1645050050182-padding {
  padding-top: 0px !important;
  padding-bottom: 0px !important;
  padding-left: 0px !important;
  padding-right: 0px !important;
}
/* HubSpot Styles (mobile) */
@media (max-width: 767px) {
  .dnd_area-row-2-padding {
    padding-bottom: 100px !important;
  }
}
</style> 
 
  <div class="body-wrapper   hs-content-id-70018798342 hs-site-page page "> 
    
   <main class="body-container-wrapper"> 
    <div class="container-fluid body-container"> 
     <div class="row-fluid-wrapper"> 
      <div class="row-fluid"> 
       <div class="span12 widget-span widget-type-cell " style="" data-widget-type="cell" data-x="0" data-w="12"> 
        <div class="row-fluid-wrapper row-depth-1 row-number-1 dnd_area-row-0-background-color dnd_area-row-0-margin dnd_area-row-0-background-layers dnd-section dnd_area-row-0-padding"> 
         <div class="row-fluid "> 
          <div class="span12 widget-span widget-type-cell dnd-column" style="" data-widget-type="cell" data-x="0" data-w="12"> 
           <div class="row-fluid-wrapper row-depth-1 row-number-2 dnd-row"> 
            <div class="row-fluid "> 
             <div class="span12 widget-span widget-type-custom_widget dnd-module" style="" data-widget-type="custom_widget" data-x="0" data-w="12"> 
              <div id="hs_cos_wrapper_widget_1649080736589" class="hs_cos_wrapper hs_cos_wrapper_widget hs_cos_wrapper_type_module widget-type-rich_text" style="" data-hs-cos-general-type="widget" data-hs-cos-type="module">
               <span id="hs_cos_wrapper_widget_1649080736589_" class="hs_cos_wrapper hs_cos_wrapper_widget hs_cos_wrapper_type_rich_text" style="" data-hs-cos-general-type="widget" data-hs-cos-type="rich_text"><h1 style="text-align: center; font-size: 70px; line-height: 60px; font-family: Quincy CF;"><span style="color: #ffffff;">CAREERS</span></h1> <h2 style="text-align: center; font-size: 30px; line-height: 1.3; font-family: Playfair Display; color: #ffffff;">The right opportunity isn't always advertised.</h2>
<p style="text-align: center; font-size: 20px; line-height: 27px; font-family: Playfair Display; color: #ffffff;">The Burke Group partners with organizations to identify exceptional professionals for leadership and specialized roles.</p>
<p style="text-align: center; font-size: 20px; line-height: 27px; font-family: Playfair Display; color: #ffffff;">Explore our current opportunities below, or connect with us confidentially to be considered for future searches.</p></span>
              </div> 
             </div>
             <!--end widget-span --> 
            </div>
            <!--end row--> 
           </div>
           <!--end row-wrapper --> 
          </div>
          <!--end widget-span --> 
         </div>
         <!--end row--> 
        </div>
        <!--end row-wrapper --> 
        <div class="row-fluid-wrapper row-depth-1 row-number-3 dnd_area-row-1-padding dnd_area-row-1-background-color dnd-section dnd_area-row-1-background-layers"> 
         <div class="row-fluid "> 
          <div class="span12 widget-span widget-type-cell dnd-column" style="" data-widget-type="cell" data-x="0" data-w="12"> 
           <div class="row-fluid-wrapper row-depth-1 row-number-4 dnd-row"> 
            <div class="row-fluid "> 
             <div class="span12 widget-span widget-type-custom_widget dnd-module" style="" data-widget-type="custom_widget" data-x="0" data-w="12"> 
              <div id="hs_cos_wrapper_widget_1649103854058" class="hs_cos_wrapper hs_cos_wrapper_widget hs_cos_wrapper_type_module" style="" data-hs-cos-general-type="widget" data-hs-cos-type="module"> 
               <hr style="
  width: 62px;
  border: 0 none;
  border-bottom-width: 3px;
  border-bottom-style: solid;
  border-bottom-color: rgba(201, 173, 133, 1.0);
  margin-left: auto;
  margin-right: auto;
  margin-top: 0;
  margin-bottom: 0;
  background-color: transparent !important;
">
              </div> 
             </div>
             <!--end widget-span --> 
            </div>
            <!--end row--> 
           </div>
           <!--end row-wrapper --> 
          </div>
          <!--end widget-span --> 
         </div>
         <!--end row--> 
        </div>
        <!--end row-wrapper --> 
        <div class="row-fluid-wrapper row-depth-1 row-number-5 dnd_area-row-2-background-layers dnd_area-row-2-padding dnd_area-row-2-background-color dnd-section"> 
         <div class="row-fluid "> 
          <div class="span12 widget-span widget-type-cell dnd-column" style="" data-widget-type="cell" data-x="0" data-w="12"> 
           <div class="row-fluid-wrapper row-depth-1 row-number-6 dnd-row"> 
            <div class="row-fluid "> 
             <div class="span12 widget-span widget-type-custom_widget dnd-module" style="" data-widget-type="custom_widget" data-x="0" data-w="12"> 
              <div id="hs_cos_wrapper_widget_1648828144712" class="hs_cos_wrapper hs_cos_wrapper_widget hs_cos_wrapper_type_module" style="" data-hs-cos-general-type="widget" data-hs-cos-type="module"> 
               <div class="jobs__files-overflow" id="current-opportunities" aria-label="Current job opportunities">
                <?php if (!empty($burke_ats_feed['unavailable'])) : ?>
                <p class="jobs__feedback" role="status">Current opportunities are temporarily unavailable. Please try again shortly, or <a href="<?php echo esc_url(home_url('/connect')); ?>">connect with The Burke Group</a>.</p>
                <?php elseif (empty($burke_ats_feed['jobs'])) : ?>
                <p class="jobs__feedback">There are no current openings. Please check back soon, or <a href="<?php echo esc_url(home_url('/connect')); ?>">connect with The Burke Group</a> to discuss future opportunities.</p>
                <?php else : ?>
                <?php foreach ($burke_ats_feed['jobs'] as $burke_ats_job) : ?>
                <div class="jobs__files-item">
                 <div class="jobs__title">
                  <p class="title"><?php echo esc_html($burke_ats_job['job_title']); ?></p>
                  <?php $burke_ats_date = !empty($burke_ats_job['start_date']) ? DateTimeImmutable::createFromFormat('!Y-m-d', $burke_ats_job['start_date']) : false; ?>
                  <?php if ($burke_ats_date) : ?><p class="date">Posted on <?php echo esc_html($burke_ats_date->format('M j Y')); ?></p><?php endif; ?>
                 </div>
                 <div class="jobs__conditions">
                  <?php if (!empty($burke_ats_job['job_type'])) : ?><p><?php echo esc_html($burke_ats_job['job_type']); ?></p><?php endif; ?>
                  <?php if (!empty($burke_ats_job['salary'])) : ?><p>Salary: <?php echo esc_html($burke_ats_job['salary']); ?></p><?php endif; ?>
                 </div>
                 <div class="jobs__ubications">
                  <?php if (!empty($burke_ats_job['location'])) : ?>
                  <img src="<?php echo esc_url(get_template_directory_uri() . '/hubfs/location-sign-svgrepo-com.svg'); ?>" alt="" aria-hidden="true">
                  <p><?php echo esc_html($burke_ats_job['location']); ?></p>
                  <?php endif; ?>
                 </div>
                 <div class="jobs__button">
                  <a href="<?php echo esc_url($burke_ats_job['detail_url']); ?>" target="_blank" rel="noopener noreferrer">More Details<span class="hs-screen-reader-text">: <?php echo esc_html($burke_ats_job['job_title']); ?> (opens in a new tab)</span></a>
                 </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
               </div>
               <?php if (empty($burke_ats_feed['unavailable']) && (int) ($burke_ats_feed['pagination']['last_page'] ?? 1) > 1) : ?>
               <nav class="jobs__pagination" aria-label="Job listing pages">
                <?php if ($burke_ats_page > 1) : ?><a href="<?php echo esc_url(add_query_arg('ats_jobs_page', $burke_ats_page - 1, get_permalink()) . '#current-opportunities'); ?>">&larr; Previous</a><?php endif; ?>
                <span>Page <?php echo esc_html($burke_ats_page); ?> of <?php echo esc_html($burke_ats_feed['pagination']['last_page']); ?></span>
                <?php if (!empty($burke_ats_feed['pagination']['has_more'])) : ?><a href="<?php echo esc_url(add_query_arg('ats_jobs_page', $burke_ats_page + 1, get_permalink()) . '#current-opportunities'); ?>">Next &rarr;</a><?php endif; ?>
               </nav>
               <?php endif; ?> 
              </div> 
             </div>
             <!--end widget-span --> 
            </div>
            <!--end row--> 
           </div>
           <!--end row-wrapper --> 
          </div>
          <!--end widget-span --> 
         </div>
         <!--end row--> 
        </div>
        <!--end row-wrapper --> 
        <div class="row-fluid-wrapper row-depth-1 row-number-7 dnd_area-row-3-background-image dnd-section dnd_area-row-3-background-layers closing-cta"> 
         <div class="row-fluid "> 
          <div class="span12 widget-span widget-type-cell dnd-column" style="" data-widget-type="cell" data-x="0" data-w="12"> 
           <div class="closing-cta__wrap">
            <div class="closing-cta__block">
             <h2>Your next<br>opportunity may not be posted yet.</h2>
             <p>Many of the conversations that lead to exceptional career moves begin long before a position is advertised.</p>
             <p>If you're an accomplished professional considering what's next, we welcome a confidential introduction.</p>
             <a class="closing-cta__link" href="https://www.theburkegroup.com/connect">Connect With The Burke Group →</a>
            </div>
            <div class="closing-cta__block">
             <h2>Looking for<br>the right person?</h2>
             <p>The right hire can shape far more than a position. It can influence a team, strengthen a culture and help move an organization forward.</p>
             <p>For more than four decades, The Burke Group has partnered with organizations to identify and attract exceptional talent for leadership, professional and specialized roles.</p>
             <p>Our approach is thoughtful, discreet and highly personalized. We take the time to understand your organization, your culture and what success in the role truly requires; then we go to market on your behalf.</p>
             <em>Qualifications matter. Alignment makes the difference.</em>
             <a class="closing-cta__link" href="https://www.theburkegroup.com/request-a-consult">Start a Search With The Burke Group →</a>
            </div>
           </div>
          </div>
          <!--end widget-span --> 
         </div>
         <!--end row--> 
        </div>
        <!--end row-wrapper --> 
       </div>
       <!--end widget-span --> 
      </div> 
     </div> 
    </div> 
   </main> 
  
  </div> 
 
<?php
get_footer();
?>