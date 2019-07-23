	(function() {
    if(window.requestAnimationFrame) return;

    var lastTime = 0;
    var vendors = ['ms', 'moz', 'webkit', 'o'];
    for (var x = 0; x < vendors.length && !window.requestAnimationFrame; ++x) {
        window.requestAnimationFrame = window[vendors[x] + 'RequestAnimationFrame'];
        window.cancelAnimationFrame = window[vendors[x] + 'CancelAnimationFrame'] || window[vendors[x] + 'CancelRequestAnimationFrame'];
    }
    if (!window.requestAnimationFrame) window.requestAnimationFrame = function(callback, element) {
        var currTime = new Date().getTime();
        var timeToCall = Math.max(0, 16 - (currTime - lastTime));
        var id = window.setTimeout(function() {
            callback();
        }, timeToCall);
        lastTime = currTime + timeToCall;
        return id;
    };
    if (!window.cancelAnimationFrame) window.cancelAnimationFrame = function(id) {
        clearTimeout(id);
    };
  }());

	(function(){
		var meteor = function(x,y,r){
			this.x = x;
			this.y = y;
			this.r = r;
			this.o = Math.random();
			this.l = 0;
		};

		var Scene = function(fCanvas){
			this.width = 0;
			this.height = 0;
			this.fCanvas = fCanvas;
			
			this.ftxt = this.fCanvas.getContext("2d");
			
			this.stars = [];
			this.meteors = new Array(4);
		};

		Scene.prototype = {
			constructor: Scene,

			init: function(){
				this.fCanvas.width = this.width = document.documentElement.clientWidth || window.screen.width;
				// this.fCanvas.height = this.height = (document.documentElement.clientHeight/3) || (window.screen.height/3);
				this.fCanvas.height = this.height = document.documentElement.clientHeight || window.screen.height;
				this.bRender();
			},

			//出现流星
			animate: function(){
				for(var i = 0;i < this.meteors.length;i++){
					this.resetMeteor(i);
				}
				var obj = this;
				requestAnimationFrame(function(){
					obj.fRender();
					requestAnimationFrame(arguments.callee);
				});
			},

			//渲染流星
			fRender: function(){
				this.ftxt.clearRect(0,0,this.width,this.height);
				for(var i = 0;i < this.meteors.length;i++){
		      var line = this.ftxt.createLinearGradient(this.meteors[i].x,this.meteors[i].y,this.meteors[i].x + this.meteors[i].l,this.meteors[i].y - 1 / 2 * this.meteors[i].l);
		      line.addColorStop(0,"rgba(224,224,224," + this.meteors[i].o + ")");
		      line.addColorStop(0.4,"rgba(224,224,224," + this.meteors[i].o + ")");
		      line.addColorStop(1,"rgba(224,224,224,0)");
		      this.ftxt.strokeStyle = line;
		      this.ftxt.lineWidth = this.meteors[i].r;

		      this.ftxt.beginPath();
		      this.ftxt.moveTo(this.meteors[i].x,this.meteors[i].y);
		      this.ftxt.lineTo(this.meteors[i].x + this.meteors[i].l,this.meteors[i].y - 1 / 2 * this.meteors[i].l);
		      this.ftxt.closePath();

		      this.ftxt.stroke();

		      if(this.meteors[i].x >= this.width || this.meteors[i].y >= this.height * 2 / 3){
		        if(this.meteors[i].l > 0) this.meteors[i].l -= 4;
		        else{
		          this.resetMeteor(i);
		        }
		      }   
		      else{
		        this.meteors[i].x -= 4;
		        this.meteors[i].y += 2;
		        if(this.meteors[i].l <= Math.ceil(this.height / 2.5)){
		          this.meteors[i].l += 3;
		        }
		      }
		    }
			},

			//渲染静态的星星和月亮
			bRender: function(){
				for(var i = 0;i < 300;i++){
		      var x = Math.ceil(Math.random() * this.width);
		      var y = Math.ceil(Math.random() * this.height * 4 / 5);
		      var o = Math.random() * 150 / y;
		      var r = Math.ceil(Math.random() * 1);

		      if(y < this.height / 3) this.stars.push(new meteor(x,y,r));

		    //   this.btxt.fillStyle = "rgba(255,255,255," + o +")";
		    //   this.btxt.beginPath();
		    //   this.btxt.arc(x,y,r,0,2 * Math.PI,false);
		    //   this.btxt.closePath();
		    //   this.btxt.fill();
		    }

		    // var grd = this.btxt.createRadialGradient(200,150,20,200,150,this.height * 2 / 3);
			//   grd.addColorStop(0,"rgba(255,255,245,1)");
			//   grd.addColorStop(0.1,"rgba(255,255,245,1)");
			//   grd.addColorStop(0.11,"rgba(255,255,200,0.1)");
			//   grd.addColorStop(0.9,"rgba(4,129,227,0)");
			//   this.btxt.fillStyle = grd;
			//   this.btxt.fillRect(0,0,this.width,this.height);
			},

			//重置流星组
			resetMeteor: function(index){
				var i = Math.floor(Math.random() * this.stars.length);
				this.meteors[index] = new meteor(this.stars[i].x,this.stars[i].y,this.stars[i].r);
			}
		};

		window.Scene = Scene;
	}());