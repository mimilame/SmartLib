"use strict";

function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _instanceof(left, right) { if (right != null && typeof Symbol !== "undefined" && right[Symbol.hasInstance]) { return !!right[Symbol.hasInstance](left); } else { return left instanceof right; } }

function _classCallCheck(instance, Constructor) { if (!_instanceof(instance, Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

var _THREE = THREE,
    WebGLRenderer = _THREE.WebGLRenderer,
    Scene = _THREE.Scene,
    PerspectiveCamera = _THREE.PerspectiveCamera,
    Mesh = _THREE.Mesh,
    Color = _THREE.Color,
    Vector3 = _THREE.Vector3,
    SplineCurve = _THREE.SplineCurve,
    Path = _THREE.Path,
    Object3D = _THREE.Object3D,
    MeshBasicMaterial = _THREE.MeshBasicMaterial,
    ShapeGeometry = _THREE.ShapeGeometry,
    FontLoader = _THREE.FontLoader;

var getRandomFloat = function getRandomFloat(min, max) {
  return Math.random() * (max - min) + min;
};

var getRandomInt = function getRandomInt(min, max) {
  return Math.floor(Math.random() * (max - min + 1)) + min;
};
/* --------------------------- */

/* ----------- CORE ---------- */


var windowWidth = window.innerWidth;
var windowHeight = window.innerHeight;

var Webgl =
/*#__PURE__*/
function () {
  function Webgl(w, h) {
    _classCallCheck(this, Webgl);

    this.meshCount = 0;
    this.meshListeners = [];
    this.renderer = new WebGLRenderer({
      antialias: true,
      alpha: true
    });
    this.renderer.setPixelRatio(window.devicePixelRatio);
    this.scene = new Scene();
    this.camera = new PerspectiveCamera(50, w / h, 1, 1000);
    this.camera.position.set(0, 0, 10);
    this.dom = this.renderer.domElement;
    this.update = this.update.bind(this);
    this.resize = this.resize.bind(this);
    this.resize(w, h); // set render size
  }

  _createClass(Webgl, [{
    key: "add",
    value: function add(mesh) {
      this.scene.add(mesh);
      if (!mesh.update) return;
      this.meshListeners.push(mesh.update);
      this.meshCount++;
    }
  }, {
    key: "remove",
    value: function remove(mesh) {
      var idx = this.meshListeners.indexOf(mesh.update);
      if (idx < 0) return;
      this.scene.remove(mesh);
      this.meshListeners.splice(idx, 1);
      this.meshCount--;
    }
  }, {
    key: "update",
    value: function update() {
      var i = this.meshCount;

      while (--i >= 0) {
        this.meshListeners[i].apply(this, null);
      }

      this.renderer.render(this.scene, this.camera);
    }
  }, {
    key: "resize",
    value: function resize(w, h) {
      this.camera.aspect = w / h;
      this.camera.updateProjectionMatrix();
      this.renderer.setSize(w, h);
    }
  }]);

  return Webgl;
}();

var webgl = new Webgl(windowWidth, windowHeight);
document.body.appendChild(webgl.dom);
/* --------- CORE END -------- */

/* --------------------------- */

/* --------------------------- */

/* ------ CREATING ZONE ------ */

var COLORS = ['#4062BB', '#52489C', '#59C3C3', '#F45B69'];

var WindLine =
/*#__PURE__*/
function (_Mesh) {
  _inherits(WindLine, _Mesh);

  function WindLine() {
    var _this;

    var _ref = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
        _ref$nbrOfPoints = _ref.nbrOfPoints,
        nbrOfPoints = _ref$nbrOfPoints === void 0 ? getRandomFloat(3, 5) : _ref$nbrOfPoints,
        _ref$length = _ref.length,
        length = _ref$length === void 0 ? getRandomFloat(5, 8) : _ref$length,
        _ref$disruptedOrienta = _ref.disruptedOrientation,
        disruptedOrientation = _ref$disruptedOrienta === void 0 ? getRandomFloat(-0.2, 0.2) : _ref$disruptedOrienta,
        _ref$speed = _ref.speed,
        speed = _ref$speed === void 0 ? 0.003 : _ref$speed,
        _ref$color = _ref.color,
        color = _ref$color === void 0 ? new Color('#000000') : _ref$color;

    _classCallCheck(this, WindLine);

    // Create the points of the line
    var points = [];
    var segmentLength = length / nbrOfPoints;
    points.push(new Vector3(0, 0, 0));

    for (var i = 0; i < nbrOfPoints; i++) {
      var pos = segmentLength * i;
      points.push(new Vector3(pos - getRandomFloat(-2.1, 2.1), pos + segmentLength * i, 0));
    } // Intance the geometry


    var curve = new SplineCurve(points);
    var path = new Path(curve.getPoints(50));
    var geometry = path.createPointsGeometry(50);
    var line = new MeshLine();
    line.setGeometry(geometry); // Material

    var dashArray = 2;
    var dashRatio = 0.99;
    var dashOffsetRight = 1.01;
    var dashOffsetLeft = dashArray * dashRatio;
    _this = _possibleConstructorReturn(this, _getPrototypeOf(WindLine).call(this, line.geometry, new MeshLineMaterial({
      lineWidth: 0.05,
      dashArray: dashArray,
      dashRatio: dashRatio,
      dashOffset: dashOffsetLeft,
      opacity: 0,
      transparent: true,
      depthWrite: false,
      color: color
    })));

    _this.position.set(getRandomFloat(-10, 10), getRandomFloat(-6, 5), getRandomFloat(-2, 10));

    _this.speed = speed;
    _this.dying = dashOffsetRight;
    _this.update = _this.update.bind(_assertThisInitialized(_this));
    return _this;
  }

  _createClass(WindLine, [{
    key: "update",
    value: function update() {
      this.material.uniforms.dashOffset.value -= this.speed;
      var opacityTargeted = this.material.uniforms.dashOffset.value > this.dying + 0.25 ? 1 : 0;
      this.material.uniforms.opacity.value += (opacityTargeted - this.material.uniforms.opacity.value) * 0.08;
    }
  }, {
    key: "isDied",
    value: function isDied() {
      return this.material.uniforms.dashOffset.value < this.dying;
    }
  }]);

  return WindLine;
}(Mesh);

var Wind =
/*#__PURE__*/
function (_Object3D) {
  _inherits(Wind, _Object3D);

  function Wind() {
    var _this2;

    _classCallCheck(this, Wind);

    _this2 = _possibleConstructorReturn(this, _getPrototypeOf(Wind).call(this));
    _this2.lines = [];
    _this2.lineNbr = -1;
    _this2.update = _this2.update.bind(_assertThisInitialized(_this2));
    return _this2;
  }

  _createClass(Wind, [{
    key: "addWindLine",
    value: function addWindLine() {
      var line = new WindLine({
        color: new Color(COLORS[getRandomInt(0, COLORS.length - 1)])
      });
      this.lines.push(line);
      this.add(line);
      this.lineNbr++;
    }
  }, {
    key: "removeWindLine",
    value: function removeWindLine() {
      this.remove(this.lines[0]);
      this.lines[0] = null;
      this.lines.shift();
      this.lineNbr--;
    }
  }, {
    key: "update",
    value: function update() {
      if (Math.random() < 0.65) {
        this.addWindLine();
      }

      var i;

      for (i = this.lineNbr; i >= 0; i--) {
        this.lines[i].update();

        if (this.lines[i].isDied()) {
          this.removeWindLine();
        }
      }
    }
  }]);

  return Wind;
}(Object3D);

var AnimatedText =
/*#__PURE__*/
function (_Object3D2) {
  _inherits(AnimatedText, _Object3D2);

  function AnimatedText(text, font) {
    var _this3;

    var _ref2 = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {},
        _ref2$size = _ref2.size,
        size = _ref2$size === void 0 ? 0.3 : _ref2$size,
        _ref2$letterSpacing = _ref2.letterSpacing,
        letterSpacing = _ref2$letterSpacing === void 0 ? 0.03 : _ref2$letterSpacing,
        _ref2$color = _ref2.color,
        color = _ref2$color === void 0 ? '#000000' : _ref2$color;

    _classCallCheck(this, AnimatedText);

    _this3 = _possibleConstructorReturn(this, _getPrototypeOf(AnimatedText).call(this));
    _this3.basePosition = 0;
    _this3.size = size;
    var letters = Array.from(text);
    letters.forEach(function (letter) {
      if (letter === ' ') {
        _this3.basePosition += size * 0.5;
      } else {
        var shape = font.generateShapes(letter, size, 1);
        var geom = new ShapeGeometry(shape);
        geom.mergeVertices();
        geom.computeBoundingBox();
        var mat = new MeshBasicMaterial({
          color: color,
          opacity: 0,
          transparent: true
        });
        var mesh = new Mesh(geom, mat);
        mesh.position.x = _this3.basePosition;
        _this3.basePosition += geom.boundingBox.max.x + letterSpacing;

        _this3.add(mesh);
      }
    });
    return _this3;
  }

  _createClass(AnimatedText, [{
    key: "show",
    value: function show() {
      var duration = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0.6;
      var tm = new TimelineLite();
      tm.set({}, {}, "+=".concat(duration * 1.1));
      this.children.forEach(function (letter) {
        var data = {
          opacity: 0,
          position: -0.5
        };
        tm.to(data, duration, {
          opacity: 1,
          position: 0,
          ease: Back.easeOut.config(2),
          onUpdate: function onUpdate() {
            letter.material.opacity = data.opacity;
            letter.position.y = data.position;
            letter.position.z = data.position * 2;
            letter.rotation.x = data.position * 2;
          }
        }, "-=".concat(duration - 0.03));
      });
    }
  }]);

  return AnimatedText;
}(Object3D); // START
// load font
// https://gero3.github.io/facetype.js/


var fontLoader = new FontLoader();
var fontAsset = fontLoader.parse(fontFile);
setTimeout(function () {
  var text = new AnimatedText('Animated Dashed Lines', fontAsset);
  text.position.x -= text.basePosition * 0.5;
  text.position.y -= 0.5;
  webgl.add(text);
  text.show();
}, 1000);
var windLines = new Wind();
webgl.add(windLines); // animate lines

/* ---- CREATING ZONE END ---- */

/* --------------------------- */

/* --------------------------- */

/* ------- CORE FOOTER ------- */

var CameraMouseControl =
/*#__PURE__*/
function () {
  function CameraMouseControl(camera) {
    _classCallCheck(this, CameraMouseControl);

    this.camera = camera;
    this.lookAt = new Vector3();
    this.position = {
      x: 0,
      y: 0
    };
    this.handleMouseMove = this.handleMouseMove.bind(this);
    this.update = this.update.bind(this);
    document.body.addEventListener('mousemove', this.handleMouseMove);
  }

  _createClass(CameraMouseControl, [{
    key: "handleMouseMove",
    value: function handleMouseMove(event) {
      this.position.x = -(event.clientX / window.innerWidth - 0.5) * 8;
      this.position.y = (event.clientY / window.innerHeight - 0.5) * 4;
    }
  }, {
    key: "update",
    value: function update() {
      this.camera.position.x += (this.position.x - this.camera.position.x) * 0.05;
      this.camera.position.y += (this.position.y - this.camera.position.y) * 0.05;
      this.camera.lookAt(this.lookAt);
    }
  }]);

  return CameraMouseControl;
}();

var cameraControl = new CameraMouseControl(webgl.camera);

function _onResize() {
  windowWidth = window.innerWidth;
  windowHeight = window.innerHeight;
  webgl.resize(windowWidth, windowHeight);
}

window.addEventListener('resize', _onResize);
window.addEventListener('orientationchange', _onResize);
/* ---- LOOP ---- */

function _loop() {
  webgl.update();
  cameraControl.update();
  requestAnimationFrame(_loop);
}

_loop();
/* ----- CORE FOOTER END ----- */

/* --------------------------- */
