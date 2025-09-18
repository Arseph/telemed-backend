<html xmlns="http://www.w3.org/1999/xhtml"><script src="chrome-extension://mjnbclmflcpookeapghfhapeffmpodij/injected_content.js"></script><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<title>Prime HRIS/Personal Data Sheet (Revised 2016)</title>
<link rel="icon" href="{{ asset('public/img/doh.png') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link href="{{ asset('public/dist/css/jquery.toast.min.css') }}" rel="stylesheet">
<style>
html {
  scroll-behavior: smooth;
  -webkit-print-color-adjust: exact !important;
}
.page { background-color:white; position:relative; z-index:0; }
.vector { position:absolute; z-index:1; }
.image { position:absolute; z-index:2; }
.text { position:absolute; z-index:3; opacity:inherit; white-space:nowrap; }
.titles {color: white !important;}
.annotation { position:absolute; z-index:5; }
.control { position:absolute; z-index:10; }
.annotation2 { position:absolute; z-index:7; }
.dummyimg { vertical-align: top; border: none; }
.button-85 {
  padding: 0.6em 2em;
  border: none;
  outline: none;
  color: rgb(255, 255, 255);
  background: #111;
  cursor: pointer;
  position: relative;
  z-index: 0;
  border-radius: 10px;
  user-select: none;
  -webkit-user-select: none;
  touch-action: manipulation;
  position:fixed;
  height:35px;
  top:15px;
  right:40px;
  z-index: 200;
}
.btn-print {
  padding: 0.6em 2em;
  border: none;
  outline: none;
  color: rgb(255, 255, 255);
  background: #111;
  cursor: pointer;
  position: relative;
  z-index: 0;
  border-radius: 10px;
  user-select: none;
  -webkit-user-select: none;
  touch-action: manipulation;
  position:fixed;
  height:35px;
  top:15px;
  right:165px;
  z-index: 200;
}
.btn-separate {
  padding: 0.6em 2em;
  border: none;
  outline: none;
  color: rgb(255, 255, 255);
  background: #235;
  cursor: pointer;
  position: relative;
  z-index: 0;
  border-radius: 10px;
  user-select: none;
  -webkit-user-select: none;
  touch-action: manipulation;
  position:fixed;
  height:15px;
  top:15px;
  right:350px;
  z-index: 200;
  text-decoration: none;
  font-family: Arial, sans-serif;
  font-size: 15px;
}
.my-float{
  margin-top:12px;
}
.button-85:before {
  content: "";
  background: linear-gradient(
    45deg,
    #ff0000,
    #ff7300,
    #fffb00,
    #48ff00,
    #00ffd5,
    #002bff,
    #7a00ff,
    #ff00c8,
    #ff0000
 );
  position: absolute;
  top: -2px;
  left: -2px;
  background-size: 400%;
  z-index: -1;
  filter: blur(5px);
  -webkit-filter: blur(5px);
  width: calc(100% + 4px);
  height: calc(100% + 4px);
  animation: glowing-button-85 20s linear infinite;
  transition: opacity 0.3s ease-in-out;
  border-radius: 10px;
}
@keyframes glowing-button-85 {
  0% {
    background-position: 0 0;
  }
  50% {
    background-position: 400% 0;
  }
  100% {
    background-position: 0 0;
  }
}

.button-85:after {
  z-index: -1;
  content: "";
  position: absolute;
  width: 100%;
  height: 100%;
  background: #222;
  left: 0;
  top: 0;
  border-radius: 10px;
}
.topnav {
  overflow: hidden;
  background-color: #333;
  position: fixed;
  width: 100%;
  z-index: 100;
}

.topnav a {
  float: left;
  color: #f2f2f2;
  text-align: center;
  padding: 14px 16px;
  text-decoration: none;
  font-size: 17px;
}

.topnav a:hover {
  background-color: #ddd;
  color: black;
}
.pg {
  margin-left: 25%;
}
.topnav a.active {
  background-color: #04AA6D;
  color: white;
}
@media print {
  @page {
    size: legal;
    margin: 0;
  }
  .topnav,#print-pds,.btn-print,.btn-separate {
    display: none;
  }
}
</style>
</head>
<body>
<div id="printall">
	@include('pdf.pds.page1')
  <br>
</div>
</body>
    <script src="{{ asset('public/assets/libs/jquery/dist/jquery.min.js') }}"></script>
    <script src="https://code.jquery.com/jquery-3.6.1.js" integrity="sha256-3zlB5s2uwoUzrXK3BT7AX3FyvojsraNFxCc2vC/7pNI="crossorigin="anonymous"></script>
<script>document.documentElement.style.display = '' </script>
<script src="{{ asset('public/js/printThis.js') }}"></script>
<script src="{{ asset('public/dist/js/jquery.toast.min.js') }}"></script>
<script>
  var pr;
	$('#print-pds').click(function() {
      $('#printall').printThis({
      	loadCSS: "{!!asset('public/css/printst.css')!!}"
      });
  });
  $('.btn-print').click(function() {
    if(!pr) {
      $.toast({
          heading: 'Error',
          text: 'Please select page to print',
          showHideTransition: 'fade',
          position: 'top-center',
          icon: 'error'
      })
    } else {
      $(pr).printThis({
        loadCSS: "{!!asset('public/css/printst.css')!!}"
      });
    }
  });
  $(window).scroll(function () {
    var sc = $(window).scrollTop();
    if(sc > 0 && sc < 1728) {
      pr = "#page1";
      $('.pg').html('<b>1/4</b>')
      $('#act1').addClass('active')
      $('#act2').removeClass('active')
      $('#act3').removeClass('active')
      $('#act4').removeClass('active')
    } else if(sc > 1728 && sc < 3500) {
      pr = ".page-2";
      $('.pg').html('<b>2/4</b>')
      $('#act1').removeClass('active')
      $('#act2').addClass('active')
      $('#act3').removeClass('active')
      $('#act4').removeClass('active')
    } else if(sc > 3500 && sc < 5258) {
      pr = ".page-3";
      $('#act1').removeClass('active')
      $('#act2').removeClass('active')
      $('#act3').addClass('active')
      $('#act4').removeClass('active')
      $('.pg').html('<b>3/4</b>')
    } else if(sc > 5258 && sc < 6042) {
      pr = "#page4";
      $('#act1').removeClass('active')
      $('#act2').removeClass('active')
      $('#act3').removeClass('active')
      $('#act4').addClass('active')
      $('.pg').html('<b>4/4</b>')
    }
  });
</script>
<grammarly-desktop-integration data-grammarly-shadow-root="true"></grammarly-desktop-integration></html>
