<?php

/*
* Rough and ready: here some variables for the proof of concept. 
*/
$apikey = "thisisasecret"; // will be sent with the POST, should be unique for each client.
$pathtoXeTeX = "/home/micz/Documents/github/XeTeX-Theme4BooktypePDF/XeTeX-Theme/"; // where all the XeTeX files and themes are.

/*
* read configuration files, like the paper sizes, margins, etc.
*/
$pagepresets = json_decode(file_get_contents("_config/config_papersizes.json"), true);
/*
* The default values for the form are in an array, see end of this file.
* Obviously, these values would come from the application. This is just
* to illustrate how it works.
*/
$formvals = fill_default_values(); 

/*
* See if this comes from the form post
*/
if(isset($_POST['Action'])) {
  /*
  * Yes, we got some post values.
  * Read the values from the posted form.
  */
  $FORM = get_post_values();
  /*
  * Creat the config file for XeTeX
  */
  // read template file
  $XeTeXconfig = file_get_contents('_assets/xetex/variables_tpl.tex');
  // replace values
  $find = array();
  $replace   = array();
  foreach($FORM['var'] as $key => $value) {
    array_push($find, "{".$key."}");
    array_push($replace, "{".$value."}");
  }
  
  $newXeTeXconfig = str_replace($find, $replace, $XeTeXconfig);
  // write template file
  file_put_contents("../XeTeX-Theme/BookConfig/variables.tex", $newXeTeXconfig);
  /*
  * Run XeTeX (twice, to make sure the table of content worked out)
  */
  chdir ("../XeTeX-Theme/");
  exec("xelatex book-master-xe.tex");
  exec("xelatex book-master-xe.tex");
  /*
  * Move and rename the generated PDF
  */
  $move = "mv book-master-xe.pdf ../htdocs/pdf/".$FORM['var']['varBookTheme']."--".$FORM['PageDefinition'].".pdf";
  exec($move);
  /*
  * Create a download link and a link to go back to the form
  */
  HTML_print_start();
  print "
  <center>
    <h1>PDF successfully generated</h1>
    <a href='pdf/".$FORM['var']['varBookTheme']."--".$FORM['PageDefinition'].".pdf' target='_blank' class='btn btn-success'>Download PDF</a>
    <a href='' class='btn btn-default'>Start again</a>
  </center>
  ";
  HTML_print_end();
} else {
  /*
  * Display the form
  */
  HTML_print_form();
}

/*
*************************************************************
* STARTING FUNCTIONS ****************************************
*************************************************************
*/

/*
* Reading and interpreting the values from the post form
*/
function get_post_values() {
  global $_POST;
  global $formvals; // the default values for XeTeX config
  global $pagepresets; // page measurement presets
  $return = array();
  // fill the array with all the defaults that might come up, but empty
  foreach($formvals as $key => $value) {
    $return['var'][$key] = "";
  }
  foreach($_POST as $key => $value) {
    // check if the key is valid
    if(array_key_exists($key, $formvals)) {
      $return['var'][$key] = $value;
    }
    // now take the preset values from JSON and add to the XeTeX config
    if($key == "PageDefinition") {
      $return['PageDefinition'] = $value;
      $return['var']['varPaperWidth'] = $pagepresets[$_POST[$key]]['width'];
      $return['var']['varPaperHeight'] = $pagepresets[$_POST[$key]]['height'];
      $return['var']['varBindingOffset'] = $pagepresets[$_POST[$key]]['binding'];
      $return['var']['varMarginTop'] = $pagepresets[$_POST[$key]]['margintop'];
      $return['var']['varMarginBottom'] = $pagepresets[$_POST[$key]]['marginbottom'];
      $return['var']['varMarginLeft'] = $pagepresets[$_POST[$key]]['marginleft'];
      $return['var']['varMarginRight'] = $pagepresets[$_POST[$key]]['marginright'];
    }
  }
  return $return;
}
/*
* HTML to create the form
*/
function HTML_print_form() {
  global $formvals;
  global $pagepresets;
  
  HTML_print_start();

  print "<form role=\"form\" data-toggle=\"validator\" action=\"\" method=\"post\" class=\"form-horizontal\">
<fieldset>
<legend>Book metadata and measurements for the PDF</legend>";

  foreach($formvals as $key=>$value) {
    if( // we show a preset pulldown for the paper margins and sizes, so hide the individual text input fields
    $key != "varPaperHeight" &&
    $key != "varBindingOffset" &&
    $key != "varMarginTop" &&
    $key != "varMarginBottom" &&
    $key != "varMarginLeft" &&
    $key != "varMarginRight"
    ) {
    print "
<div class=\"form-group\">";
    if( // show a pulldown instead of text fields with measurements and margins
      $key == "varPaperWidth") {
    print "
  <label class=\"col-md-4 control-label\" for=\"PageDefinition\">Page Definition</label>";
    } else {
    print "
  <label class=\"col-md-4 control-label\" for=\"$key\">$key</label>";
    }
    print "
  <div class=\"col-md-8\">";
    if( // for these we want to use a textarea field
      $key == "varDedication" OR 
      $key == "varAcknowledge" OR
      $key == "varShortdescription" OR
      $key == "varLongdescription") {  
      print "                   
    <textarea class=\"form-control\" id=\"$key\" name=\"$key\">$value</textarea>";
    } elseif( // these are true or false radio buttons
      $key == "varShowHalfTitle" OR 
      $key == "varShowTitlePage" OR 
      $key == "varShowColophon" OR 
      $key == "varShowDedication" OR 
      $key == "varShowToContents" OR 
      $key == "varShowToFigures" OR 
      $key == "varShowToTables") {
      print "
    <label class=\"radio-inline\" for=\"radios-0\">
      <input name=\"$key\" id=\"$key-0\" value=\"true\""; 
      if($value == "true") { print " checked=\"checked\" "; } 
      print "type=\"radio\">
      true
    </label> 
    <label class=\"radio-inline\" for=\"radios-1\">
      <input name=\"$key\" id=\"$key-1\" value=\"false\""; 
      if($value != "true") { print " checked=\"checked\" "; } 
      print "type=\"radio\">
      false
    </label> ";
    } elseif( // this is a selection of all available themes
      $key == "varBookTheme") {
      print "
    <select id=\"$key\" name=\"$key\" class=\"form-control\">";
    //get all dirs which contain theme TeX files
      $files = scandir("../XeTeX-Theme/XeTeX/themes/");
      foreach($files as $file) {
       if( // check if dir and omit . and ..
        is_dir("../XeTeX-Theme/XeTeX/themes/".$file) &&
        $file != "." && 
        $file != "..") {
        print "\n      <option value=\"$file\""; if($value == $file) { print " selected"; } print ">$file</option>";    
       }
      }
      print "
    </select>";
    } elseif( // show a pulldown instead of text fields with measurements and margins
      $key == "varPaperWidth") { // we showed it (see above) so show nothing for these values
      print "
    <select id=\"PageDefinition\" name=\"PageDefinition\" class=\"form-control\">";
      foreach($pagepresets as $key => $pagepreset) {
        print "\n      <option value=\"$key\""; if($value == $file) { print " selected"; } print ">$key</option>";    
      }
      print "
    </select>";
    } elseif( // this is a selection of all available themes
      $key == "varBookLanguage") {
      print "
    <select id=\"$key\" name=\"$key\" class=\"form-control\">";
      print "\n      <option value=\"german\""; if($value == "german") { print " selected"; } print ">german</option>";
      print "\n      <option value=\"english\""; if($value == "english") { print " selected"; } print ">english</option>";
      print "
    </select>";
    } else {
      print "
    <input id=\"$key\" name=\"$key\" value=\"$value\" placeholder=\"$value\" class=\"form-control input-md\" type=\"text\">";
    }
    print "
  </div>
</div>
";  
    }
  }
  // end of form
  print "
<!-- Submit button-->
<div class=\"form-group\">
  <label class=\"col-md-4 control-label\" for=\"Submit\"></label>  
  <div class=\"col-md-8\">
    <button type=\"submit\" name=\"Action\" value=\"submitform\" class=\"btn btn-info\">Create PDF</button>
  </div>
</div>
</form>
";
  HTML_print_end();
}
/*
* HTML for the header and body tag
*/
function HTML_print_start() {
  print "
<!DOCTYPE html>
<html lang=\"en\">
  <head>
    <meta charset=\"utf-8\">
    <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    
    <title>XeTeX for Booktype PDF</title>

    <!-- Latest compiled and minified CSS -->
    <link rel=\"stylesheet\" href=\"_assets/bootstrap/css/bootstrap3.3.6.min.css\">
    
    <!-- Latest compiled and minified JavaScript -->
    <script src=\"_assets/js/jquery-1.11.3.min.js\"></script>
    <script src=\"_assets/bootstrap/js/bootstrap3.3.6.min.js\"></script>
    
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src=\"_assets/bootstrap/js/html5shiv3.7.2.min.js\"></script>
      <script src=\"_assets/bootstrap/js/respond1.4.2.min.js\"></script>
    <![endif]-->
  </head>
  <body>
    <div class=\"container\">
      <div class=\"row\">
        <div class=\"col-lg-8\">
  ";
}
/*
* HTML for the end of the page
*/
function HTML_print_end() {
print "
        </div>
      </div><!-- /.row -->
    </div><!-- /.container -->
</body>

</html>
";
}
/*
* Set the default values for the form. Again, just for the proof of concept. 
* These values should come from the application.
*/ 
function fill_default_values() {
  $formvals = array(
  "varBookLanguage" => "english", // english, german
  "varBookTheme" => "default", // available themes: default, bauhaus, victoriannovel, gothicflower
  // FRONT MATTER
  "varShowHalfTitle" => "true", // (true|false)
  "varShowTitlePage" => "true", // (true|false)
  "varShowColophon" => "true", // (true|false)
  "varShowDedication" => "true", // (true|false)
  "varShowToContents" => "true", // (true|false)
  "varShowToFigures" => "false", // (true|false) opt. overwritten by variables-theme.tex
  "varShowToTables" => "false", // (true|false) opt. overwritten by variables-theme.tex
  // PAPER FORMAT AND MARGINS
  // use cm, mm or in (for inch). for more info:
  // https://en.wikibooks.org/wiki/LaTeX/Lengths
  "varPaperWidth" => "4.72in", 
  "varPaperHeight" => "7.48in", 
  "varBindingOffset" => "0cm", 
  "varMarginTop" => "2cm", // measurement excluding header!
  "varMarginBottom" => "2cm",
  "varMarginLeft" => "1.2cm",
  "varMarginRight" => "1.2cm",
  // METADATA
  // the collophon will print all filled in information
  // if you want to hide something, leave it empty like this: ""
  // IMPORTANT: all variables here must be initiated, do not comment them out
  "varTitle" => "Dracula meets Frankenstein",
  "varSubtitle" => "What happens when two worlds collide?",
  "varAuthors" => "Author van Book",
  "varURLAuthor" => "www.authorwebsite.com",
  "varDedication" => "Dedicating this to Somebody Special

And another line of dedication, because there is always somebody else you should have mentioned---and they will never, ever forgive you.",
  "varShorttitle" => "",
  "varCopyrightdate" => "2007, 2008, 2012",
  "varCopyrightholder" => "Copy R. Holder",
  "varPublicationdate" => "1st October 2010",
  "varPublisher" => "Publishing Company Ltd.",
  "varPublishercity" => "Berlin",
  "varURLPublisher" => "www.publishersite.com",
  "varCoverdesign" => "C. O. ver Designer",
  "varCoverimage" => "Image Cover",
  "varPhotographyby" => "Photo G. Rapher",
  "varShortdescription" => "",
  "varLongdescription" => "",
  "varEbookISBN" => "0 123 12345 1",
  "varPrintISBN" => "9 876 54321 0",
  "varEditedby" => "Edward Itor",
  "varTextby" => "Tex T. Writer",
  "varTranslationby" => "Babel Fish",
  "varIntroductionby" => "Intro Ducer",
  "varIllustrationby" => "Illu Stration",
  "varResearch" => "R. E. Search",
  "varLectorate" => "Hanibal Lector",
  "varProofreading" => "Proof U. Need",
  "varRightsclearing" => "Rechte Klar",
  "varTypeface" => "Ubuntu Type Font Version 4.2 2015",
  "varPrintercompany" => "International Printers",
  "varPrintercountry" => "Denmark",
  "varPrintedon" => "White Recycling Paper",
  "varPapercertification" => "This paper is climate neutral made from recycled materials and not bleached.",
  "varBookbinder" => "Book The Binder",
  "varAcknowledge" => "Acknowledgement In the creative arts and scientific literature, an acknowledgment (also spelled acknowledgement) is an expression of gratitude for assistance in creating an original work.In the creative arts and scientific literature, an acknowledgment (also spelled acknowledgement) is an expression of gratitude for assistance in creating an original work.

In the creative arts and scientific literature, an acknowledgment (also spelled acknowledgement) is an expression of gratitude for assistance in creating an original work.",
  "varEdition" => "3rd edition",
  "varBibliographicinformation" => "Bibliographic information.In the creative arts and scientific literature, an acknowledgment (also spelled acknowledgement) is an expression of gratitude for assistance in creating an original work.",
  "varCreationTool" => "This book was created, edited and prepared for print using Booktype, the browser based book authoring platform. www.booktype.pro"
  );
  return $formvals;
}
// echo $output; // you  might want to uncomment this line while debugging to see what the remote server sent
?>