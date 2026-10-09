<?php 
$qry=$_POST['qry'];
include("SYD_Class.php");
$coder=new sydClass();
$coder->Table($qry,"Reports_table","r");
?>
<style>
#Reports_table {
  font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
  border-collapse: collapse;
  width: 100%;
}
#Reports_table td, #Reports_table th {
  border: 1px solid #ddd;
  padding: 8px;
}
#Reports_table tr:hover {background-color: #ddd;}
#Reports_table th {
  padding-top: 12px;
  padding-bottom: 12px;
  text-align: left;
  background-color: #696cff !important;
  color: white;
  font-size: 13px;
}
#tbl_exam_update {
  font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

</style>
  <style type="text/css">
    body {
      font: 12pt "Trebuchet MS", Arial, Helvetica, sans-serif;
      line-height: 1.3;
    }

    @page {
      /* switch to landscape */
      size: portrait;
      /* set page margins */
      margin: 0.5cm;
      /* Default footers */
  	  @bottom-left {
        content: "Department of Strategy";
      }
      @bottom-right {
        content: counter(page) " of " counter(pages);
      }
   	}

    /* footer, header - position: fixed */
    #header {
      position: fixed;
      width: 100%;
      top: 0;
      left: 0;
      right: 0;
      color: white !important;
    }

    #footer {
      position: fixed;
      width: 100%;
      bottom: 0;
      left: 0;
      right: 0;
    }

    /* .thead text-white{
      color: white !important;
    } */

    /* Fix overflow of headers and content */
    body {
      /*padding-top: 50px;*/
    }
    .custom-page-start {
      margin-top: 20px;
    }
  </style>