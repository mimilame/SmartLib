<style>
  #preloader {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgb(0 0 0 / 90%);
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
      align-content: center;
  }
  svg{
    
    height:200px;
    
    width:100%;
    
  }

  .main{
    
    position:relative;
    
    display:flex;
    
    flex-wrap:wrap;
    
    justify-content:center;
    
    width:100%;
    
    height: 100%;
    
    margin-bottom: 4%;
    
  }

  .circle {
    
    width:20px;
    
    height: 20px;
    
    border-radius: 20px;
    
    margin-right: 14px;
    
    margin-top:20px;
    
    background-color:#9290FA;
    
  }

  .animate-cursor{
    
    fill: #0f1244;
    
    margin-right:3px;
    
    animation: cursor-animation 6s infinite;
    
    animation-timing-function: ease;
    
  }

  .circle1 {
    
    animation: circle1-animation 1.5s infinite;
    
    animation-timing-function: ease-in-out;

    
  }

  .circle2 {
    
    animation: circle2-animation 1.5s infinite;
    
    animation-timing-function: ease-in-out;
    
  }

  .circle3 {
    
    animation: circle3-animation 1.5s infinite;
    
    animation-timing-function: ease-in-out;
    
  }

  .circle4 {
    
    animation: circle4-animation 1.5s infinite;
    
    animation-timing-function: ease-in-out;
    
  }

  @keyframes circle1-animation{
    
    0%{transform: translateY(0px);}
    20%{
      transform: translateY(-30px);
      background-color: #FFDC64;
    }
    40%{
      transform: translateY(0px);
    }
    60%{transform: translateY(0px);}
    80%{transform: translateY(0px);}
    100%{transform: translateY(0px);}

  }

  @keyframes circle2-animation{
    
    0%{transform: translateY(0px);}
    20%{transform: translateY(0px);}
    40%{
      transform: translateY(-30px);
      background-color: #FF5050;
    }
    60%{
      transform: translateY(0px);;
    }
    80%{transform: translateY(0px);}
    100%{transform: translateY(0px);}
  }

  @keyframes circle3-animation{
    
    0%{transform: translateY(0px);}
    20%{transform: translateY(0px);}
    40%{transform: translateY(0px);}
    60%{
      transform: translateY(-30px);
      background-color: #7DF5A5;
    }
    80%{transform: translateY(0px);}
    100%{transform: translateY(0px);}
  }

  @keyframes circle4-animation{
    
    0%{transform: translateY(0px);}
    20%{transform: translateY(0px);}
    40%{transform: translateY(0px);}
    60%{transform: translateY(0px);}
    80%{
      transform: translateY(-30px);
      background-color: #785353;
    }
    100%{
      transform: translateY(0px);
    }
    
  }

  @keyframes cursor-animation{
    
    0%{ transform: translateY(-180px);
        }
    20%{
      transform: translateY(-180px);
      transform: translateX(-100px);
    }
    40%{
      transform: translateY(-100px);
      transform: translateX(180px);
    }
    60%{
      transform: translateY(-130px);
      transform: translateX(-130px);
    }
    80%{
      transform: translateY(-70px);
      transform: translateX(200px);
    }
    100%{transform: translateY(-180px);}
    
  }
</style>


<div class="main" id="preloader">
  
    &nbsp
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
<path style="fill:#FFDC64;" d="M503.172,358.869h-35.31c-4.875,0-8.828-3.953-8.828-8.828V67.559c0-4.875,3.953-8.828,8.828-8.828
	h35.31c4.875,0,8.828,3.953,8.828,8.828v282.483C512,354.917,508.047,358.869,503.172,358.869z"/>
<path style="fill:#00AAF0;" d="M361.931,58.731h-35.31c-4.875,0-8.828,3.953-8.828,8.828v282.483c0,4.875,3.953,8.828,8.828,8.828
	h35.31c4.875,0,8.828-3.953,8.828-8.828V67.559C370.759,62.684,366.806,58.731,361.931,58.731z"/>
<rect x="317.793" y="314.737" style="fill:#00C3FF;" width="52.966" height="17.655"/>
<path style="fill:#0096DC;" d="M361.931,358.869c3.98,0,7.215-2.684,8.318-6.303l-52.456-52.456v49.931
	c0,4.875,3.953,8.828,8.828,8.828H361.931z"/>
<rect x="317.793" y="85.208" style="fill:#00C3FF;" width="52.966" height="17.655"/>
<polygon style="fill:#00AAF0;" points="332.414,314.731 317.793,314.731 317.793,332.386 350.069,332.386 "/>
<path style="fill:#FF5050;" d="M167.724,358.869h-35.31c-4.875,0-8.828-3.953-8.828-8.828V67.559c0-4.875,3.953-8.828,8.828-8.828
	h35.31c4.875,0,8.828,3.953,8.828,8.828v282.483C176.552,354.917,172.599,358.869,167.724,358.869z"/>
<g>
	<rect x="123.586" y="85.208" style="fill:#C84146;" width="52.966" height="17.655"/>
	<rect x="123.586" y="314.737" style="fill:#C84146;" width="52.966" height="17.655"/>
</g>
<path style="fill:#959CB5;" d="M44.138,358.869H8.828c-4.875,0-8.828-3.953-8.828-8.828V67.559c0-4.875,3.953-8.828,8.828-8.828
	h35.31c4.875,0,8.828,3.953,8.828,8.828v282.483C52.966,354.917,49.013,358.869,44.138,358.869z"/>
<path style="fill:#785353;" d="M450.207,358.869h-70.621c-4.875,0-8.828-3.953-8.828-8.828V49.904c0-4.875,3.953-8.828,8.828-8.828
	h70.621c4.875,0,8.828,3.953,8.828,8.828v300.138C459.034,354.917,455.082,358.869,450.207,358.869z"/>
<path style="fill:#FFDC64;" d="M114.759,358.869H61.793c-4.875,0-8.828-3.953-8.828-8.828V32.248c0-4.875,3.953-8.828,8.828-8.828
	h52.966c4.875,0,8.828,3.953,8.828,8.828v317.793C123.586,354.917,119.634,358.869,114.759,358.869z"/>
<path style="fill:#FAEBC8;" d="M88.276,261.766c-4.879,0-8.828-3.948-8.828-8.828V147.007c0-4.879,3.948-8.828,8.828-8.828
	c4.879,0,8.828,3.948,8.828,8.828v105.931C97.103,257.818,93.155,261.766,88.276,261.766z"/>
<g>
	<path style="fill:#FFDC64;" d="M414.897,261.766c-4.879,0-8.828-3.948-8.828-8.828v-52.966c0-4.879,3.948-8.828,8.828-8.828
		c4.879,0,8.828,3.948,8.828,8.828v52.966C423.724,257.818,419.776,261.766,414.897,261.766z"/>
	<path style="fill:#FFDC64;" d="M414.897,173.49c-4.879,0-8.828-3.948-8.828-8.828v-17.655c0-4.879,3.948-8.828,8.828-8.828
		c4.879,0,8.828,3.948,8.828,8.828v17.655C423.724,169.542,419.776,173.49,414.897,173.49z"/>
</g>
<path style="fill:#7DF5A5;" d="M308.966,41.076h-70.621c-4.875,0-8.828,3.953-8.828,8.828v300.138c0,4.875,3.953,8.828,8.828,8.828
	h70.621c4.875,0,8.828-3.953,8.828-8.828V49.904C317.793,45.029,313.841,41.076,308.966,41.076z"/>
<path style="fill:#64DCA0;" class="animate-cursor" d="M293.674,275.991c-4.174-4.174-9.705-6.473-15.573-6.473c-12.186,0-22.101,9.905-22.101,22.078
	v67.273h52.966c4.875,0,8.828-3.953,8.828-8.828v-49.931L293.674,275.991z"/>
<g>
	<path style="fill:#FFDC64;" d="M273.655,252.938c-4.879,0-8.828-3.948-8.828-8.828v-26.483c0-4.879,3.948-8.828,8.828-8.828
		c4.879,0,8.828,3.948,8.828,8.828v26.483C282.483,248.99,278.535,252.938,273.655,252.938z"/>
	<path style="fill:#FFDC64;" d="M273.655,191.145c-4.879,0-8.828-3.948-8.828-8.828v-70.621c0-4.879,3.948-8.828,8.828-8.828
		c4.879,0,8.828,3.948,8.828,8.828v70.621C282.483,187.197,278.535,191.145,273.655,191.145z"/>
	<rect x="229.517" y="314.737" style="fill:#FFDC64;" width="88.276" height="17.655"/>
</g>
<rect x="256" y="314.737" style="fill:#FFC850;" width="61.793" height="17.655"/>
<rect x="229.517" y="67.564" style="fill:#FFDC64;" width="88.276" height="17.655"/>
<path style="" id="one" class="animate-cursor" d="M273.655,291.596v127.892c0,3.51,3.897,5.615,6.834,3.691l25.352-16.615l31.713,76.563
	c1.866,4.504,7.03,6.643,11.533,4.778l17.576-7.281c4.504-1.866,6.643-7.03,4.778-11.534l-31.713-76.563l29.792-6.03
	c3.451-0.698,4.735-4.958,2.246-7.447l-90.574-90.574C278.41,285.695,273.655,287.665,273.655,291.596z"/>
  
<path style="fill:#707487;" d="M185.379,358.869h35.31c4.875,0,8.828-3.953,8.828-8.828V32.248c0-4.875-3.953-8.828-8.828-8.828
	h-35.31c-4.875,0-8.828,3.953-8.828,8.828v317.793C176.552,354.917,180.504,358.869,185.379,358.869z"/>
<g>
	<path style="fill:#5B5D6E;" d="M220.69,23.421h-35.31c-4.875,0-8.828,3.953-8.828,8.828v17.655h52.966V32.248
		C229.517,27.373,225.565,23.421,220.69,23.421z"/>
	<path style="fill:#5B5D6E;" d="M185.379,358.869h35.31c4.875,0,8.828-3.953,8.828-8.828v-17.655h-52.966v17.655
		C176.552,354.917,180.504,358.869,185.379,358.869z"/>
</g>
<g>
	<rect x="52.966" y="67.564" style="fill:#FAEBC8;" width="70.621" height="17.655"/>
	<rect x="52.966" y="102.864" style="fill:#FAEBC8;" width="70.621" height="17.655"/>
	<rect x="52.966" y="279.415" style="fill:#FAEBC8;" width="70.621" height="17.655"/>
	<rect x="52.966" y="314.737" style="fill:#FAEBC8;" width="70.621" height="17.655"/>
</g>
<g>
	<rect y="85.208" style="fill:#7F8499;" width="52.966" height="17.655"/>
	<rect y="314.737" style="fill:#7F8499;" width="52.966" height="17.655"/>
</g>
<g>
	<rect x="370.759" y="67.564" style="fill:#FFDC64;" width="88.276" height="17.655"/>
	<rect x="370.759" y="102.864" style="fill:#FFDC64;" width="88.276" height="17.655"/>
	<rect x="370.759" y="279.415" style="fill:#FFDC64;" width="88.276" height="17.655"/>
	<rect x="370.759" y="314.737" style="fill:#FFDC64;" width="88.276" height="17.655"/>
</g>
<g>
	<path style="fill:#FFC850;" d="M503.172,58.731h-35.31c-4.875,0-8.828,3.953-8.828,8.828v17.655H512V67.559
		C512,62.684,508.047,58.731,503.172,58.731z"/>
	<path style="fill:#FFC850;" d="M467.862,358.869h35.31c4.875,0,8.828-3.953,8.828-8.828v-17.655h-52.966v17.655
		C459.034,354.917,462.987,358.869,467.862,358.869z"/>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
<g>
</g>
  <use id="use" xlink:href="#one" />
</svg>
    
    <div class="circle circle1">&nbsp</div>
    <div class="circle circle2">&nbsp</div>
    <div class="circle circle3">&nbsp</div>
    <div class="circle circle4">&nbsp</div>
    
    
    
  </div>