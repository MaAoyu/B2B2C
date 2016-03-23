/*
Copyright 2012, KISSY UI Library v1.30dev
MIT Licensed
build time: Jan 6 17:21
*/
/**
 * @fileOverview UIBase.Align
 * @author yiminghe@gmail.com , qiaohua@taobao.com
 */
KISSY.add('uibase/align', function(S, UA, DOM, Node) {


    /**
     * inspired by closure library by Google
     * @see http://yiminghe.iteye.com/blog/1124720
     */

    /**
     * 得到影响元素显示的父亲元素
     */
    function getOffsetParent(element) {
        // ie 这个也不是完全可行
        /**
         <div style="width: 50px;height: 100px;overflow: hidden">
         <div style="width: 50px;height: 100px;position: relative;" id="d6">
         元素 6 高 100px 宽 50px<br/>
         </div>
         </div>
         **/
//            if (UA['ie']) {
//                return element.offsetParent;
//            }
        var body = element.ownerDocument.body,
            positionStyle = DOM.css(element, 'position'),
            skipStatic = positionStyle == 'fixed' || positionStyle == 'absolute';

        for (var parent = element.parentNode;
             parent && parent != body;
             parent = parent.parentNode) {

            positionStyle = DOM.css(parent, 'position');

            skipStatic = skipStatic && positionStyle == 'static';

            var parentOverflow = DOM.css(parent, "overflow");

            // 必须有 overflow 属性，可能会隐藏掉子孙元素
            if (parentOverflow != 'visible' && (
                // 元素初始为 fixed absolute ，遇到 父亲不是 定位元素忽略
                // 否则就可以
                !skipStatic ||
                    positionStyle == 'fixed' ||
                    positionStyle == 'absolute' ||
                    positionStyle == 'relative'
                )) {
                return parent;
            }
        }
        return null;
    }

    /**
     * 获得元素的显示部分的区域
     */
    function getVisibleRectForElement(element) {
        var visibleRect = {
            left:0,
            right:Infinity,
            top:0,
            bottom:Infinity
        };

        for (var el = element; el = getOffsetParent(el);) {


            var clientWidth = el.clientWidth;

            if (
            // clientWidth is zero for inline block elements in IE.
                (!UA['ie'] || clientWidth !== 0)
            // on WEBKIT, body element can have clientHeight = 0 and scrollHeight > 0
            // && (!UA['webkit'] || clientHeight != 0 || el != body)
            // overflow 不为 visible 则可以限定其内元素
            // && (scrollWidth != clientWidth || scrollHeight != clientHeight)
            // offsetParent 已经判断过了
            //&& DOM.css(el, 'overflow') != 'visible'
                ) {
                var clientLeft = el.clientLeft,
                    clientTop = el.clientTop,
                    pos = DOM.offset(el),
                    client = {
                        left:clientLeft,
                        top:clientTop
                    };
                pos.left += client.left;
                pos.top += client.top;

                visibleRect.top = Math.max(visibleRect['top'], pos.top);
                visibleRect.right = Math.min(visibleRect.right,
                    pos.left + el.clientWidth);
                visibleRect.bottom = Math.min(visibleRect['bottom'],
                    pos.top + el.clientHeight);
                visibleRect.left = Math.max(visibleRect.left, pos.left);
            }
        }

        var scrollX = DOM.scrollLeft(),
            scrollY = DOM.scrollTop();

        visibleRect.left = Math.max(visibleRect.left, scrollX);
        visibleRect.top = Math.max(visibleRect['top'], scrollY);
        visibleRect.right = Math.min(visibleRect.right, scrollX + DOM.viewportWidth());
        visibleRect.bottom = Math.min(visibleRect['bottom'], scrollY + DOM.viewportHeight());

        return visibleRect.top >= 0 && visibleRect.left >= 0 &&
            visibleRect.bottom > visibleRect.top &&
            visibleRect.right > visibleRect.left ?
            visibleRect : null;
    }

    function isFailed(status) {
        for (var s in status) {
            if (s.indexOf("fail") === 0) {
                return true;
            }
        }
        return false;
    }

    function positionAtAnchor(alignCfg) {
        var offset = alignCfg.offset,
            node = alignCfg.node,
            points = alignCfg.points,
            self = this,
            xy,
            diff,
            p1,
            //如果没有view，就是不区分mvc
            el = self.get('el'),
            p2;

        offset = offset || [0,0];
        xy = el.offset();

        // p1 是 node 上 points[0] 的 offset
        // p2 是 overlay 上 points[1] 的 offset
        p1 = getAlignOffset(node, points[0]);
        p2 = getAlignOffset(el, points[1]);

        diff = [p2.left - p1.left, p2.top - p1.top];
        xy = {
            left: xy.left - diff[0] + (+offset[0]),
            top: xy.top - diff[1] + (+offset[1])
        };

        return positionAtCoordinate.call(self, xy, alignCfg);
    }


    function positionAtCoordinate(absolutePos, alignCfg) {
        var self = this,el = self.get('el');
        var status = {};
        var elSize = {width:el.outerWidth(),height:el.outerHeight()},
            size = S.clone(elSize);
        if (!S.isEmptyObject(alignCfg.overflow)) {
            var viewport = getVisibleRectForElement(el[0]);
            status = adjustForViewport(absolutePos, size, viewport, alignCfg.overflow || {});
            if (isFailed(status)) {
                return status;
            }
        }

        self.set("x", absolutePos.left);
        self.set("y", absolutePos.top);

        if (size.width != elSize.width || size.height != elSize.height) {
            el.width(size.width);
            el.height(size.height);
        }

        return status;

    }


    function adjustForViewport(pos, size, viewport, overflow) {
        var status = {};
        if (pos.left < viewport.left && overflow.adjustX) {
            pos.left = viewport.left;
            status.adjustX = 1;
        }
        // Left edge inside and right edge outside viewport, try to resize it.
        if (pos.left < viewport.left &&
            pos.left + size.width > viewport.right &&
            overflow.resizeWidth) {
            size.width -= (pos.left + size.width) - viewport.right;
            status.resizeWidth = 1;
        }

        // Right edge outside viewport, try to move it.
        if (pos.left + size.width > viewport.right &&
            overflow.adjustX) {
            pos.left = Math.max(viewport.right - size.width, viewport.left);
            status.adjustX = 1;
        }

        // Left or right edge still outside viewport, fail if the FAIL_X option was
        // specified, ignore it otherwise.
        if (overflow.failX) {
            status.failX = pos.left < viewport.left ||
                pos.left + size.width > viewport.right;
        }

        // Top edge outside viewport, try to move it.
        if (pos.top < viewport.top && overflow.adjustY) {
            pos.top = viewport.top;
            status.adjustY = 1;
        }

        // Top edge inside and bottom edge outside viewport, try to resize it.
        if (pos.top >= viewport.top &&
            pos.top + size.height > viewport.bottom &&
            overflow.resizeHeight) {
            size.height -= (pos.top + size.height) - viewport.bottom;
            status.resizeHeight = 1;
        }

        // Bottom edge outside viewport, try to move it.
        if (pos.top + size.height > viewport.bottom &&
            overflow.adjustY) {
            pos.top = Math.max(viewport.bottom - size.height, viewport.top);
            status.adjustY = 1;
        }

        // Top or bottom edge still outside viewport, fail if the FAIL_Y option was
        // specified, ignore it otherwise.
        if (overflow.failY) {
            status.failY = pos.top < viewport.top ||
                pos.top + size.height > viewport.bottom;
        }

        return status;
    }


    function flip(points, reg, map) {
        var ret = [];
        S.each(points, function(p) {
            ret.push(p.replace(reg, function(m) {
                return map[m];
            }));
        });
        return ret;
    }

    function flipOffset(offset, index) {
        offset[index] = -offset[index];
        return offset;
    }

    function Align() {
    }

    Align.ATTRS = {
        align: {
            // 默认不是正中，可以实现自由动画 zoom
//            value:{
//                node: null,         // 参考元素, falsy 值为可视区域, 'trigger' 为触发元素, 其他为指定元素
//                points: ['cc','cc'], // ['tr', 'tl'] 表示 overlay 的 tl 与参考节点的 tr 对齐
//                offset: [0, 0]      // 有效值为 [n, m]
//            }
        }
    };

    /**
     * 获取 node 上的 align 对齐点 相对于页面的坐标
     * @param node
     * @param align
     */
    function getAlignOffset(node, align) {
        var V = align.charAt(0),
            H = align.charAt(1),
            offset, w, h, x, y;

        if (node) {
            node = Node.one(node);
            offset = node.offset();
            w = node.outerWidth();
            h = node.outerHeight();
        } else {
            offset = { left: DOM.scrollLeft(), top: DOM.scrollTop() };
            w = DOM.viewportWidth();
            h = DOM.viewportHeight();
        }

        x = offset.left;
        y = offset.top;

        if (V === 'c') {
            y += h / 2;
        } else if (V === 'b') {
            y += h;
        }

        if (H === 'c') {
            x += w / 2;
        } else if (H === 'r') {
            x += w;
        }

        return { left: x, top: y };
    }

    Align.prototype = {

        _uiSetAlign: function(v) {
            if (S.isPlainObject(v)) {
                this.align(v.node, v.points, v.offset, v.overflow);
            }
        },

        /**
         * 对齐 Overlay 到 node 的 points 点, 偏移 offset 处
         * @param {Element} node 参照元素, 可取配置选项中的设置, 也可是一元素
         * @param {String[]} points 对齐方式
         * @param {Number[]} [offset] 偏移
         */
        align: function(node, points, offset, overflow) {
            var self = this,
                flag = {};
            // 后面会改的，先保存下
            overflow = S.clone(overflow || {});
            offset = offset && [].concat(offset) || [0,0];
            if (overflow.failX) {
                flag.failX = 1;
            }
            if (overflow.failY) {
                flag.failY = 1;
            }
            var status = positionAtAnchor.call(self, {
                node:node,
                points:points,
                offset:offset,
                overflow:flag
            });
            // 如果错误调整重试
            if (isFailed(status)) {
                if (status.failX) {
                    points = flip(points, /[lr]/ig, {
                        l:"r",
                        r:"l"
                    });
                    offset = flipOffset(offset, 0);
                }

                if (status.failY) {
                    points = flip(points, /[tb]/ig, {
                        t:"b",
                        b:"t"
                    });
                    offset = flipOffset(offset, 1);
                }
            }

            status = positionAtAnchor.call(self, {
                node:node,
                points:points,
                offset:offset,
                overflow:flag
            });

            if (isFailed(status)) {
                delete overflow.failX;
                delete overflow.failY;
                status = positionAtAnchor.call(self, {
                    node:node,
                    points:points,
                    offset:offset,
                    overflow:overflow
                });
            }
        },

        /**
         * 居中显示到可视区域, 一次性居中
         */
        center: function(node) {
            this.set('align', {
                node: node,
                points: ["cc", "cc"],
                offset: [0, 0]
            });
        }
    };

    return Align;
}, {
    requires:["ua","dom","node"]
});
/**
 *  2011-07-13 承玉 note:
 *   - 增加智能对齐，以及大小调整选项
 **//**
 * @fileOverview   UIBase
 * @author  yiminghe@gmail.com,lifesinger@gmail.com
 * @see http://martinfowler.com/eaaDev/uiArchs.html
 */
KISSY.add('uibase/base', function (S, Base, Node) {

    var UI_SET = '_uiSet',
        SRC_NODE = 'srcNode',
        ATTRS = 'ATTRS',
        HTML_PARSER = 'HTML_PARSER',
        noop = S.noop;

    function capitalFirst(s) {
        return s.charAt(0).toUpperCase() + s.substring(1);
    }

    /**
     * UIBase for class-based component
     * @class
     * @namespace
     * @name UIBase
     * @extends Base
     */
    function UIBase(config) {
        // 读取用户设置的属性值并设置到自身
        Base.apply(this, arguments);
        // 根据 srcNode 设置属性值
        // 按照类层次执行初始函数，主类执行 initializer 函数，扩展类执行构造器函数
        initHierarchy(this, config);
        // 是否自动渲染
        config && config.autoRender && this.render();
    }

    /**
     * 模拟多继承
     * init attr using constructors ATTRS meta info
     */
    function initHierarchy(host, config) {

        var c = host.constructor;

        while (c) {

            // 从 markup 生成相应的属性项
            if (config &&
                config[SRC_NODE] &&
                c.HTML_PARSER) {
                if ((config[SRC_NODE] = Node.one(config[SRC_NODE]))) {
                    applyParser.call(host, config[SRC_NODE], c.HTML_PARSER);
                }
            }

            c = c.superclass && c.superclass.constructor;
        }

        callMethodByHierarchy(host, "initializer", "constructor");

    }

    function callMethodByHierarchy(host, mainMethod, extMethod) {
        var c = host.constructor,
            extChains = [],
            ext,
            main,
            exts,
            t;

        // define
        while (c) {

            // 收集扩展类
            t = [];
            if (exts = c.__ks_exts) {
                for (var i = 0; i < exts.length; i++) {
                    ext = exts[i];
                    if (ext) {
                        if (extMethod != "constructor") {
                            //只调用真正自己构造器原型的定义，继承原型链上的不要管
                            if (ext.prototype.hasOwnProperty(extMethod)) {
                                ext = ext.prototype[extMethod];
                            } else {
                                ext = null;
                            }
                        }
                        ext && t.push(ext);
                    }
                }
            }

            // 收集主类
            // 只调用真正自己构造器原型的定义，继承原型链上的不要管 !important
            // 所以不用自己在 renderUI 中调用 superclass.renderUI 了，UIBase 构造器自动搜寻
            // 以及 initializer 等同理
            if (c.prototype.hasOwnProperty(mainMethod) && (main = c.prototype[mainMethod])) {
                t.push(main);
            }

            // 原地 reverse
            if (t.length) {
                extChains.push.apply(extChains, t.reverse());
            }

            c = c.superclass && c.superclass.constructor;
        }

        // 初始化函数
        // 顺序：父类的所有扩展类函数 -> 父类对应函数 -> 子类的所有扩展函数 -> 子类对应函数
        for (i = extChains.length - 1; i >= 0; i--) {
            extChains[i] && extChains[i].call(host);
        }
    }

    /**
     * 销毁组件
     * 顺序： 子类 destructor -> 子类扩展 destructor -> 父类 destructor -> 父类扩展 destructor
     */
    function destroyHierarchy(host) {
        var c = host.constructor,
            exts,
            d,
            i;

        while (c) {
            // 只触发该类真正的析构器，和父亲没关系，所以不要在子类析构器中调用 superclass
            if (c.prototype.hasOwnProperty("destructor")) {
                c.prototype.destructor.apply(host);
            }

            if ((exts = c.__ks_exts)) {
                for (i = exts.length - 1; i >= 0; i--) {
                    d = exts[i] && exts[i].prototype.__destructor;
                    d && d.apply(host);
                }
            }

            c = c.superclass && c.superclass.constructor;
        }
    }

    function applyParser(srcNode, parser) {
        var host = this, p, v;

        // 从 parser 中，默默设置属性，不触发事件
        for (p in parser) {
            if (parser.hasOwnProperty(p)) {
                v = parser[p];

                // 函数
                if (S.isFunction(v)) {
                    host.__set(p, v.call(host, srcNode));
                }
                // 单选选择器
                else if (S.isString(v)) {
                    host.__set(p, srcNode.one(v));
                }
                // 多选选择器
                else if (S.isArray(v) && v[0]) {
                    host.__set(p, srcNode.all(v[0]))
                }
            }
        }
    }

    UIBase.HTML_PARSER = {};

    UIBase.ATTRS = {
        // 是否已经渲染完毕
        rendered:{
            value:false
        },
        // dom 节点是否已经创建完毕
        created:{
            value:false
        }
    };

    S.extend(UIBase, Base,
        /**
         * @lends UIBase.prototype
         */
        {

            /**
             * 建立节点，先不放在 dom 树中，为了性能!
             */
            create:function () {
                var self = this;
                // 是否生成过节点
                if (!self.get("created")) {
                    self._createDom();
                    self.fire('createDom');
                    callMethodByHierarchy(self, "createDom", "__createDom");
                    self.fire('afterCreateDom');
                    self.__set("created", true);
                }
            },

            /**
             * 渲染组件到 dom 结构
             */
            render:function () {
                var self = this;
                // 是否已经渲染过
                if (!self.get("rendered")) {
                    self.create();
                    self._renderUI();
                    // 实际上是 beforeRenderUI
                    self.fire('renderUI');
                    callMethodByHierarchy(self, "renderUI", "__renderUI");
                    self.fire('afterRenderUI');
                    self._bindUI();
                    // 实际上是 beforeBindUI
                    self.fire('bindUI');
                    callMethodByHierarchy(self, "bindUI", "__bindUI");
                    self.fire('afterBindUI');
                    self._syncUI();
                    // 实际上是 beforeSyncUI
                    self.fire('syncUI');
                    callMethodByHierarchy(self, "syncUI", "__syncUI");
                    self.fire('afterSyncUI');
                    self.__set("rendered", true);
                }
                return self;
            },

            /**
             * 创建 dom 节点，但不放在 document 中
             */
            _createDom:noop,

            /**
             * 节点已经创建完毕，可以放在 document 中了
             */
            _renderUI:noop,

            /**
             * @protected
             * @function
             */
            renderUI:noop,

            /**
             * 根据属性变化设置 UI
             */
            _bindUI:function () {
                var self = this,
                    attrs = self['__attrs'],
                    attr, m;

                for (attr in attrs) {
                    if (attrs.hasOwnProperty(attr)) {
                        m = UI_SET + capitalFirst(attr);
                        if (self[m]) {
                            // 自动绑定事件到对应函数
                            (function (attr, m) {
                                self.on('after' + capitalFirst(attr) + 'Change', function (ev) {
                                    self[m](ev.newVal, ev);
                                });
                            })(attr, m);
                        }
                    }
                }
            },

            /**
             * @protected
             * @function
             */
            bindUI:noop,

            /**
             * 根据当前（初始化）状态来设置 UI
             */
            _syncUI:function () {
                var self = this,
                    attrs = self['__attrs'];
                for (var a in attrs) {
                    if (attrs.hasOwnProperty(a)) {
                        var m = UI_SET + capitalFirst(a);
                        //存在方法，并且用户设置了初始值或者存在默认值，就同步状态
                        if (self[m]
                            // 用户如果设置了显式不同步，就不同步，比如一些值从 html 中读取，不需要同步再次设置
                            && attrs[a].sync !== false
                            && self.get(a) !== undefined) {
                            self[m](self.get(a));
                        }
                    }
                }
            },

            /**
             * protected
             * @function
             */
            syncUI:noop,


            /**
             * 销毁组件
             */
            destroy:function () {
                destroyHierarchy(this);
                this.fire('destroy');
                this.detach();
            }
        },
        /**
         * @lends UIBase
         */
        {
            /**
             * 根据基类以及扩展类得到新类
             * @param {Function|Function[]} base 基类
             * @param {Function[]} exts 扩展类
             * @param {Object} px 原型 mix 对象
             * @param {Object} sx 静态 mix 对象
             * @returns {UIBase} 组合 后 的 新类
             */
            create:function (base, exts, px, sx) {
                if (S.isArray(base)) {
                    sx = px;
                    px = exts;
                    exts = base;///*@type Function[]*/
					//debug.....    
                    base = UIBase;
                }
                base = base || UIBase;
                if (S.isObject(exts)) {
                    sx = px;
                    px = exts;
                    exts = [];
                }

                function C() {
                    UIBase.apply(this, arguments);
                }

                S.extend(C, base, px, sx);

                if (exts) {

                    C.__ks_exts = exts;

                    var desc = {
                        // ATTRS:
                        // HMTL_PARSER:
                    }, constructors = exts.concat(C);

                    // [ex1,ex2],扩展类后面的优先，ex2 定义的覆盖 ex1 定义的
                    // 主类最优先
                    S.each(constructors, function (ext) {
                        if (ext) {
                            // 合并 ATTRS/HTML_PARSER 到主类
                            S.each([ATTRS, HTML_PARSER], function (K) {
                                if (ext[K]) {
                                    desc[K] = desc[K] || {};
                                    // 不覆盖主类上的定义，因为继承层次上扩展类比主类层次高
                                    // 但是值是对象的话会深度合并
                                    // 注意：最好值是简单对象，自定义 new 出来的对象就会有问题!
                                    S.mix(desc[K], ext[K], true, undefined, true);
                                }
                            });
                        }
                    });

                    S.each(desc, function (v, k) {
                        C[k] = v;
                    });

                    var prototype = {};

                    // 主类最优先
                    S.each(constructors, function (ext) {
                        if (ext) {
                            var proto = ext.prototype;
                            // 合并功能代码到主类，不覆盖
                            for (var p in proto) {
                                // 不覆盖主类，但是主类的父类还是覆盖吧
                                if (proto.hasOwnProperty(p)) {
                                    prototype[p] = proto[p];
                                }
                            }
                        }
                    });

                    S.each(prototype, function (v, k) {
                        C.prototype[k] = v;
                    });
                }
                return C;
            }
        });

    return UIBase;
}, {
    requires:["base", "node"]
});
/**
 * render 和 create 区别
 * render 包括 create ，以及把生成的节点放在 document 中
 * create 仅仅包括创建节点
 **//**
 * @fileOverview UIBase.Box
 * @author yiminghe@gmail.com
 */
KISSY.add('uibase/box', function () {

    /**
     * @class
     * @memberOf UIBase
     * @namespace
     */
    function Box() {
    }

    Box.ATTRS =
    /**
     * @lends UIBase.Box#
     */
    {
        html:{
            view:true
        },
        // 宽度
        width:{
            view:true
        },
        // 高度
        height:{
            view:true
        },
        // 容器的 class
        elCls:{
            view:true
        },
        // 容器的行内样式
        elStyle:{
            view:true
        },
        // 其他属性
        elAttrs:{
            //其他属性
            view:true
        },
        // 插入到该元素前
        elBefore:{
            view:true
        },
        el:{
            view:true
        },

        // 渲染该组件的目的容器
        render:{
            view:true
        },

        visibleMode:{
            value:"display",
            view:true
        },
        // 默认显示，但不触发事件
        visible:{
            view:true
        },

        // 从已存在节点开始渲染
        srcNode:{
            view:true
        }
    };


    Box.HTML_PARSER =
    /**
     * @private
     */
    {
        el:function (srcNode) {
            /**
             * 如果需要特殊的对现有元素的装饰行为
             */
            if (this.decorateInternal) {
                this.decorateInternal(srcNode);
            }
            return srcNode;
        }
    };

    Box.prototype =
    /**
     * @lends UIBase.Box#
     */
    {

        _uiSetVisible:function (isVisible) {
            this.fire(isVisible ? "show" : "hide");
        },

        /**
         * 显示 Overlay
         */
        show:function () {
            var self = this;
            self.render();
            self.set("visible", true);
        },

        /**
         * 隐藏
         */
        hide:function () {
            this.set("visible", false);
        }
    };

    return Box;
});
/**
 * @fileOverview UIBase.Box
 * @author yiminghe@gmail.com
 */
KISSY.add('uibase/boxrender', function (S, Node) {

    var $ = S.all;

    /**
     * @class
     * @memberOf UIBase.Box
     */
    function BoxRender() {
    }

    BoxRender.ATTRS =
    /**
     * @lends UIBase.Box.Render#
     */
    {
        el:{
            //容器元素
            setter:function (v) {
                return $(v);
            }
        },
        // 构建时批量生成，不需要执行单个
        elCls:{
            sync:false
        },
        elStyle:{
            sync:false
        },
        width:{
            sync:false
        },
        height:{
            sync:false
        },
        elTagName:{
            sync:false,
            // 生成标签名字
            value:"div"
        },
        elAttrs:{
            sync:false
        },
        html:{
            sync:false
        },
        elBefore:{
        },
        render:{},
        visible:{
        },
        visibleMode:{

        }
    };

    BoxRender.construct = constructEl;

    function constructEl(cls, style, width, height, tag, attrs, html) {
        style = style || {};
        html = html || "";
        if (typeof html !== "string") {
            html = "";
        }

        if (width) {
            style.width = typeof width == "number" ? (width + "px") : width;
        }

        if (height) {
            style.height = typeof height == "number" ? (height + "px") : height;
        }

        var styleStr = '';

        for (var s in style) {
            if (style.hasOwnProperty(s)) {
                styleStr += s + ":" + style[s] + ";";
            }
        }

        var attrStr = '';

        for (var a in attrs) {
            if (attrs.hasOwnProperty(a)) {
                attrStr += " " + a + "='" + attrs[a] + "'" + " ";
            }
        }

        return "<" + tag + (styleStr ? (" style='" + styleStr + "' ") : "")
            + attrStr + (cls ? (" class='" + cls + "' ") : "")
            + ">" + html + "<" + "/" + tag + ">";
        //return ret;
    }

    BoxRender.HTML_PARSER =
    /**
     * @ignore
     */
    {
        html:function (el) {
            return el.html();
        }
    };

    BoxRender.prototype =
    /**
     * @lends UIBase.Box.Render#
     */
    {

        __renderUI:function () {
            var self = this;
            // 新建的节点才需要摆放定位
            if (self.__boxRenderNew) {
                var render = self.get("render"),
                    el = self.get("el"),
                    elBefore = self.get("elBefore");
                if (elBefore) {
                    el.insertBefore(elBefore);
                }
                else if (render) {
                    el.appendTo(render);
                }
                else {
                    el.appendTo(document.body);
                }
            }
        },

        /**
         * 只负责建立节点，如果是 decorate 过来的，甚至内容会丢失
         * 通过 render 来重建原有的内容
         */
        __createDom:function () {
            var self = this,
                el = self.get("el");
            if (!el) {
                self.__boxRenderNew = true;
                el = new Node(constructEl(self.get("elCls"),
                    self.get("elStyle"),
                    self.get("width"),
                    self.get("height"),
                    self.get("elTagName"),
                    self.get("elAttrs"),
                    self.get("html")));
                self.__set("el", el);
            }
        },

        _uiSetElAttrs:function (attrs) {
            this.get("el").attr(attrs);
        },

        _uiSetElCls:function (cls) {
            this.get("el").addClass(cls);
        },

        _uiSetElStyle:function (style) {
            this.get("el").css(style);
        },

        _uiSetWidth:function (w) {
            this.get("el").width(w);
        },

        _uiSetHeight:function (h) {
            var self = this;
            self.get("el").height(h);
        },

        _uiSetHtml:function (c) {
            this.get("el").html(c);
        },

        _uiSetVisible:function (isVisible) {
            var el = this.get("el"),
                visibleMode = this.get("visibleMode");
            if (visibleMode == "visibility") {
                el.css("visibility", isVisible ? "visible" : "hidden");
            } else {
                el.css("display", isVisible ? "" : "none");
            }
        },

        show:function () {
            var self = this;
            self.render();
            self.set("visible", true);
        },

        hide:function () {
            this.set("visible", false);
        },

        __destructor:function () {
            //S.log("box __destructor");
            var el = this.get("el");
            if (el) {
                el.detach();
                el.remove();
            }
        }
    };

    return BoxRender;
}, {
    requires:['node']
});
/**
 * @fileOverview close extension for kissy dialog
 * @author yiminghe@gmail.com
 */
KISSY.add("uibase/close", function() {
    function Close() {
    }

    var HIDE = "hide";
    Close.ATTRS = {
        closable: {
            view:true
        },
        closeAction:{
            value:HIDE
        }
    };

    var actions = {
        hide:HIDE,
        destroy:"destroy"
    };

    Close.prototype = {

        __bindUI:function() {

            var self = this,
                closeBtn = self.get("view").get("closeBtn");
            closeBtn && closeBtn.on("click", function(ev) {
                self[actions[self.get("closeAction")] || HIDE]();
                ev.preventDefault();
            });
        }
    };
    return Close;

});/**
 * @fileOverview close extension for kissy dialog
 * @author yiminghe@gmail.com
 */
KISSY.add("uibase/closerender", function(S, Node) {

    var CLS_PREFIX = 'ext-';

    function Close() {
    }

    Close.ATTRS = {
        closable: {             // 是否需要关闭按钮
            value: true
        },
        closeBtn:{
        }
    };

    Close.HTML_PARSER = {
        closeBtn:function(el) {
            return el.one("." + this.get("prefixCls") + CLS_PREFIX + 'close');
        }
    };

    Close.prototype = {
        _uiSetClosable:function(v) {
            var self = this,
                closeBtn = self.get("closeBtn");
            if (closeBtn) {
                if (v) {
                    closeBtn.css("display", "");
                } else {
                    closeBtn.css("display", "none");
                }
            }
        },
        __renderUI:function() {
            var self = this,
                closeBtn = self.get("closeBtn"),
                el = self.get("el");

            if (!closeBtn && el) {
                closeBtn = new Node("<a " +
                    "tabindex='0' " +
                    "role='button' " +
                    "class='" + this.get("prefixCls") + CLS_PREFIX + "close" + "'>" +
                    "<span class='" +
                    this.get("prefixCls") + CLS_PREFIX + "close-x" +
                    "'>关闭<" + "/span>" +
                    "<" + "/a>").appendTo(el);
                self.set("closeBtn", closeBtn);
            }
        },

        __destructor:function() {

            var self = this,
                closeBtn = self.get("closeBtn");
            closeBtn && closeBtn.detach();
        }
    };

    return Close;

}, {
    requires:["node"]
});/**
 * @fileOverview constrain extension for kissy
 * @author yiminghe@gmail.com, 乔花<qiaohua@taobao.com>
 */
KISSY.add("uibase/constrain", function(S, DOM, Node) {

    function Constrain() {

    }

    Constrain.ATTRS = {
        constrain:{
            //不限制
            //true:viewport限制
            //node:限制在节点范围
            value:false
        }
    };

    /**
     * 获取受限区域的宽高, 位置
     * @return {Object | undefined} {left: 0, top: 0, maxLeft: 100, maxTop: 100}
     */
    function _getConstrainRegion(constrain) {
        var ret;
        if (!constrain) {
            return ret;
        }
        var el = this.get("el");
        if (constrain !== true) {
            constrain = Node.one(constrain);
            ret = constrain.offset();
            S.mix(ret, {
                maxLeft: ret.left + constrain.outerWidth() - el.outerWidth(),
                maxTop: ret.top + constrain.outerHeight() - el.outerHeight()
            });
        }
        // 没有指定 constrain, 表示受限于可视区域
        else {
            //不要使用 viewportWidth()
            //The innerWidth attribute, on getting,
            //must return the viewport width including the size of a rendered scroll bar (if any).
            //On getting, the clientWidth attribute returns the viewport width
            //excluding the size of a rendered scroll bar (if any)
            //  if the element is the root element 
            var vWidth = document.documentElement.clientWidth;
            ret = { left: DOM.scrollLeft(), top: DOM.scrollTop() };
            S.mix(ret, {
                maxLeft: ret.left + vWidth - el.outerWidth(),
                maxTop: ret.top + DOM.viewportHeight() - el.outerHeight()
            });
        }

        return ret;
    }

    Constrain.prototype = {

        __renderUI:function() {
            var self = this,
                attrs = self.getAttrs(),
                xAttr = attrs["x"],
                yAttr = attrs["y"],
                oriXSetter = xAttr["setter"],
                oriYSetter = yAttr["setter"];
            xAttr.setter = function(v) {
                var r = oriXSetter && oriXSetter.call(self, v);
                if (r === undefined) {
                    r = v;
                }
                if (!self.get("constrain")) {
                    return r;
                }
                var _ConstrainExtRegion = _getConstrainRegion.call(
                    self, self.get("constrain"));
                return Math.min(Math.max(r,
                    _ConstrainExtRegion.left),
                    _ConstrainExtRegion.maxLeft);
            };
            yAttr.setter = function(v) {
                var r = oriYSetter && oriYSetter.call(self, v);
                if (r === undefined) {
                    r = v;
                }
                if (!self.get("constrain")) {
                    return r;
                }
                var _ConstrainExtRegion = _getConstrainRegion.call(
                    self, self.get("constrain"));
                return Math.min(Math.max(r,
                    _ConstrainExtRegion.top),
                    _ConstrainExtRegion.maxTop);
            };
            self.addAttr("x", xAttr);
            self.addAttr("y", yAttr);
        }
    };


    return Constrain;

}, {
    requires:["dom","node"]
});/**
 * @fileOverview 里层包裹层定义，适合mask以及shim
 * @author yiminghe@gmail.com
 */
KISSY.add("uibase/contentbox", function() {

    function ContentBox() {
    }

    ContentBox.ATTRS = {
        //层内容
        content:{
            view:true,
            sync:false
        },
        contentEl:{
            view:true
        },

        contentElAttrs:{
            view:true
        },
        contentElStyle:{
            view:true
        },
        contentTagName:{
            view:true
        }
    };

    ContentBox.prototype = {    };

    return ContentBox;
});/**
 * @fileOverview 里层包裹层定义，适合mask以及shim
 * @author yiminghe@gmail.com
 */
KISSY.add("uibase/contentboxrender", function (S, Node, BoxRender) {

    /**
     * @class 内层容器渲染混元类
     * @name Render
     * @memberOf UIBase.ContentBox
     */
    function ContentBoxRender() {
    }

    ContentBoxRender.ATTRS =
    /**
     * @lends UIBase.ContentBox.Render
     */
    {
        /**
         * 内容容器节点
         * @type String|Node
         */
        contentEl:{},
        contentElAttrs:{},
        contentElCls:{},
        contentElStyle:{},
        contentTagName:{
            value:"div"
        },
        /**
         * 内层内容
         * @type String|Node
         */
        content:{
            sync:false
        }
    };

    /*
     ! contentEl 只能由组件动态生成
     */
    ContentBoxRender.HTML_PARSER = {
        content:function (el) {
            return el[0].innerHTML;
        }
    };

    var constructEl = BoxRender.construct;

    ContentBoxRender.prototype = {

        // no need ,shift create work to __createDom
        __renderUI:function () {
        },

        __createDom:function () {
            var self = this,
                contentEl,
                c = self.get("content"),
                el = self.get("el"),
                html = "",
                elChildren = S.makeArray(el[0].childNodes);

            if (elChildren.length) {
                html = el[0].innerHTML
            }

            // el html 和 c 相同，直接 append el的子节点
            if (c == html) {
                c = "";
            }

            contentEl = new Node(constructEl(
                self.get("prefixCls") + "contentbox "
                    + (self.get("contentElCls") || ""),
                self.get("contentElStyle"),
                undefined,
                undefined,
                self.get("contentTagName"),
                self.get("contentElAttrs"),
                c)).appendTo(el);
            self.__set("contentEl", contentEl);
            // on content,then read from box el
            if (!c) {
                for (var i = 0, l = elChildren.length; i < l; i++) {
                    contentEl.append(elChildren[i]);
                }
            } else if (typeof c !== 'string') {
                contentEl.append(c);
            }
        },

        _uiSetContentElCls:function (cls) {
            this.get("contentEl").addClass(cls);
        },

        _uiSetContentElAttrs:function (attrs) {
            this.get("contentEl").attr(attrs);
        },

        _uiSetContentElStyle:function (v) {
            this.get("contentEl").css(v);
        },

        _uiSetContent:function (c) {
            if (typeof c == "string") {
                this.get("contentEl").html(c);
            } else {
                this.get("contentEl").empty().append(c);
            }
        }
    };

    return ContentBoxRender;
}, {
    requires:["node", "./boxrender"]
});/**
 * @fileOverview drag extension for position
 * @author yiminghe@gmail.com
 */
KISSY.add("uibase/drag", function(S) {


    function Drag() {
    }

    Drag.ATTRS = {
        handlers:{
            value:[]
        },
        draggable:{value:true}
    };

    Drag.prototype = {

        _uiSetHandlers:function(v) {
            if (v && v.length > 0 && this.__drag) {
                this.__drag.set("handlers", v);
            }
        },

        __bindUI:function() {
            var Draggable = S.require("dd/draggable");
            var self = this,
                el = self.get("el");
            if (self.get("draggable") && Draggable) {
                self.__drag = new Draggable({
                    node:el
                });
            }
        },

        _uiSetDraggable:function(v) {

            var self = this,
                d = self.__drag;
            if (!d) {
                return;
            }
            if (v) {
                d.detach("drag");
                d.on("drag", self._dragExtAction, self);
            } else {
                d.detach("drag");
            }
        },

        _dragExtAction:function(offset) {
            this.set("xy", [offset.left,offset.top])
        },
        /**
         *
         */
        __destructor:function() {
            //S.log("DragExt __destructor");
            var d = this.__drag;
            d && d.destroy();
        }

    };
    return Drag;

});/**
 * @fileOverview loading mask support for overlay
 * @author yiminghe@gmail.com
 */
KISSY.add("uibase/loading", function() {

    function Loading() {
    }

    Loading.prototype = {
        loading:function() {
            this.get("view").loading();
        },

        unloading:function() {
            this.get("view").unloading();
        }
    };

    return Loading;

});/**
 * @fileOverview loading mask support for overlay
 * @author yiminghe@gmail.com
 */
KISSY.add("uibase/loadingrender", function(S, Node) {

    function Loading() {
    }

    Loading.prototype = {
        loading:function() {
            var self = this;
            if (!self._loadingExtEl) {
                self._loadingExtEl = new Node("<div " +
                    "class='" +
                    this.get("prefixCls") +
                    "ext-loading'" +
                    " style='position: absolute;" +
                    "border: none;" +
                    "width: 100%;" +
                    "top: 0;" +
                    "left: 0;" +
                    "z-index: 99999;" +
                    "height:100%;" +
                    "*height: expression(this.parentNode.offsetHeight);" + "'/>")
                    .appendTo(self.get("el"));
            }
            self._loadingExtEl.show();
        },

        unloading:function() {
            var lel = this._loadingExtEl;
            lel && lel.hide();
        }
    };

    return Loading;

}, {
    requires:['node']
});/**
 * @fileOverview mask extension for kissy
 * @author yiminghe@gmail.com
 */
KISSY.add("uibase/mask", function() {


    function Mask() {
    }

    Mask.ATTRS = {
        mask:{
            value:false
        }
    };

    Mask.prototype = {

        _uiSetMask:function(v) {
            var self = this;
            if (v) {
                self.on("show", self.get("view")._maskExtShow, self.get("view"));
                self.on("hide", self.get("view")._maskExtHide, self.get("view"));
            } else {
                self.detach("show", self.get("view")._maskExtShow, self.get("view"));
                self.detach("hide", self.get("view")._maskExtHide, self.get("view"));
            }
        }
    };


    return Mask;
}, {requires:["ua"]});/**
 * @fileOverview mask extension for kissy
 * @author yiminghe@gmail.com
 */
KISSY.add("uibase/maskrender", function(S, UA, Node) {

    /**
     * 多 position 共享一个遮罩
     */
    var mask,
        ie6 = (UA['ie'] === 6),
        px = "px",
        $ = Node.all,
        win = $(window),
        doc = $(document),
        iframe,
        num = 0;

    function docWidth() {
        return  ie6 ? (doc.width() + px) : "100%";
    }

    function docHeight() {
        return ie6 ? (doc.height() + px) : "100%";
    }

    function initMask() {
        mask = $("<div " +
            //"tabindex='-1' " +
            "class='" +
            this.get("prefixCls") + "ext-mask'/>")
            .prependTo("body");
        mask.css({
            "position":ie6 ? "absolute" : "fixed", // mask 不会撑大 docWidth
            left:0,
            top:0,
            width: docWidth(),
            "height": docHeight()
        });
        if (ie6) {
            //ie6 下最好和 mask 平行
            iframe = $("<" + "iframe " +
                //"tabindex='-1' " +
                "style='position:absolute;" +
                "left:" + "0px" + ";" +
                "top:" + "0px" + ";" +
                "background:red;" +
                "width:" + docWidth() + ";" +
                "height:" + docHeight() + ";" +
                "filter:alpha(opacity=0);" +
                "z-index:-1;'/>").insertBefore(mask)
        }

        /**
         * 点 mask 焦点不转移
         */
        mask.unselectable();
        mask.on("mousedown click", function(e) {
            e.halt();
        });
    }

    function Mask() {
    }

    var resizeMask = S.throttle(function() {
        var v = {
            width : docWidth(),
            height : docHeight()
        };
        mask.css(v);
        iframe && iframe.css(v);
    }, 50);


    Mask.prototype = {

        _maskExtShow:function() {
            var self = this;
            if (!mask) {
                initMask.call(self);
            }
            var zIndex = {
                "z-index": self.get("zIndex") - 1
            },
                display = {
                    "display":""
                };
            mask.css(zIndex);
            iframe && iframe.css(zIndex);
            num++;
            if (num == 1) {
                mask.css(display);
                iframe && iframe.css(display);
                if (ie6) {
                    win.on("resize scroll", resizeMask);
                }
            }
        },

        _maskExtHide:function() {
            num--;
            if (num <= 0) {
                num = 0;
            }
            if (!num) {
                var display = {
                    "display":"none"
                };
                mask && mask.css(display);
                iframe && iframe.css(display);
                if (ie6) {
                    win.detach("resize scroll", resizeMask);
                }
            }
        },

        __destructor:function() {
            this._maskExtHide();
        }

    };

    return Mask;
}, {
    requires:["ua","node"]
});/**
 * @fileOverview position and visible extension，可定位的隐藏层
 * @author yiminghe@gmail.com
 */
KISSY.add("uibase/position", function(S) {

    function Position() {
    }

    Position.ATTRS = {
        x: {
            view:true
        },
        y: {
            view:true
        },
        xy: {
            // 相对 page 定位, 有效值为 [n, m], 为 null 时, 选 align 设置
            setter: function(v) {

                var self = this,
                    xy = S.makeArray(v);

                /*
                 属性内分发特别注意：
                 xy -> x,y

                 */
                if (xy.length) {
                    xy[0] && self.set("x", xy[0]);
                    xy[1] && self.set("y", xy[1]);
                }
                return v;
            },
            /**
             * xy 纯中转作用
             */
            getter:function() {
                return [this.get("x"),this.get("y")];
            }
        },
        zIndex: {
            view:true
        }
    };


    Position.prototype = {

        /**
         * 移动到绝对位置上, move(x, y) or move(x) or move([x, y])
         * @param {number|Array.<number>} x
         * @param {number=} y
         */
        move: function(x, y) {
            var self = this;
            if (S.isArray(x)) {
                y = x[1];
                x = x[0];
            }
            self.set("xy", [x,y]);
        }


    };

    return Position;
});/**
 * @fileOverview position and visible extension，可定位的隐藏层
 * @author  yiminghe@gmail.com
 */
KISSY.add("uibase/positionrender", function() {

    var ZINDEX = 9999;

    function Position() {
    }

    Position.ATTRS = {
        x: {
            // 水平方向绝对位置
            valueFn:function() {
                // 读到这里时，el 一定是已经加到 dom 树中了，否则报未知错误
                // el 不在 dom 树中 offset 报错的
                // 最早读就是在 syncUI 中，一点重复设置(读取自身 X 再调用 _uiSetX)无所谓了
                return this.get("el") && this.get("el").offset().left;
            }
        },
        y: {
            // 垂直方向绝对位置
            valueFn:function() {
                return this.get("el") && this.get("el").offset().top;
            }
        },
        zIndex: {
            value: ZINDEX
        }
    };


    Position.prototype = {

        __renderUI:function() {
            this.get("el").addClass(this.get("prefixCls") + "ext-position");
        },

        _uiSetZIndex:function(x) {
            this.get("el").css("z-index", x);
        },
        _uiSetX:function(x) {
            this.get("el").offset({
                left:x
            });
        },
        _uiSetY:function(y) {
            this.get("el").offset({
                top:y
            });
        }
    };

    return Position;
});/**
 * @fileOverview resize extension using resizable
 * @author yiminghe@gmail.com
 */
KISSY.add("uibase/resize", function(S) {
    function Resize() {
    }

    Resize.ATTRS = {
        resize:{
            value:{
            }
        }
    };

    Resize.prototype = {
        __destructor:function() {
            this.resizer && this.resizer.destroy();
        },
        _uiSetResize:function(v) {

            var Resizable = S.require("resizable"),
                self = this;
            if (Resizable) {
                self.resizer && self.resizer.destroy();
                v.node = self.get("el");
                v.autoRender = true;
                if (v.handlers) {
                    self.resizer = new Resizable(v);
                }
            }

        }
    };


    return Resize;
});/**
 * @fileOverview shim for ie6 ,require box-ext
 * @author yiminghe@gmail.com
 */
KISSY.add("uibase/shimrender", function(S, Node) {

    function Shim() {
        //S.log("shim init");
    }


    Shim.ATTRS = {
        shim:{
            value:true
        }
    };
    Shim.prototype = {

        _uiSetShim:function(v) {
            var self = this,el = self.get("el");
            if (v && !self.__shimEl) {
                self.__shimEl = new Node("<" + "iframe style='position: absolute;" +
                    "border: none;" +
                    "width: expression(this.parentNode.offsetWidth);" +
                    "top: 0;" +
                    "opacity: 0;" +
                    "filter: alpha(opacity=0);" +
                    "left: 0;" +
                    "z-index: -1;" +
                    "height: expression(this.parentNode.offsetHeight);" + "'/>");
                el.prepend(self.__shimEl);
            } else if (!v && self.__shimEl) {
                self.__shimEl.remove();
                delete self.__shimEl;
            }
        }
    };

    return Shim;
}, {
    requires:['node']
});/**
 * @fileOverview support standard mod for component
 * @author yiminghe@gmail.com
 */
KISSY.add("uibase/stdmod", function() {


    function StdMod() {
    }

    StdMod.ATTRS = {
        header:{
            view:true
        },
        body:{
            view:true
        },
        footer:{
            view:true
        },
        bodyStyle:{
            view:true
        },
        footerStyle:{
            view:true
        },
        headerStyle:{
            view:true
        },
        headerContent:{
            view:true
        },
        bodyContent:{
            view:true
        },
        footerContent:{
            view:true
        }
    };


    StdMod.prototype = {};

    return StdMod;

});/**
 * @fileOverview support standard mod for component
 * @author yiminghe@gmail.com
 */
KISSY.add("uibase/stdmodrender", function(S, Node) {


    var CLS_PREFIX = "stdmod-";

    function StdMod() {
    }

    StdMod.ATTRS = {
        header:{
        },
        body:{
        },
        footer:{
        },
        bodyStyle:{
        },
        footerStyle:{

        },
        headerStyle:{

        },
        headerContent:{},
        bodyContent:{},
        footerContent:{}
    };

    StdMod.HTML_PARSER = {
        header:function(el) {
            return el.one("." + this.get("prefixCls") + CLS_PREFIX + "header");
        },
        body:function(el) {
            return el.one("." + this.get("prefixCls") + CLS_PREFIX + "body");
        },
        footer:function(el) {
            return el.one("." + this.get("prefixCls") + CLS_PREFIX + "footer");
        }
    };

    function renderUI(self, part) {
        var el = self.get("contentEl"),
            partEl = self.get(part);
        if (!partEl) {
            partEl = new Node("<div class='" + self.get("prefixCls") + CLS_PREFIX + part + "'/>")
                .appendTo(el);
            self.set(part, partEl);
        }
    }

    StdMod.prototype = {

        _setStdModContent:function(part, v) {
            if (S.isString(v)) {
                this.get(part).html(v);
            } else {
                this.get(part).html("");
                this.get(part).append(v);
            }
        },
        _uiSetBodyStyle:function(v) {

            this.get("body").css(v);

        },
        _uiSetHeaderStyle:function(v) {

            this.get("header").css(v);

        },
        _uiSetFooterStyle:function(v) {

            this.get("footer").css(v);
        },
        _uiSetBodyContent:function(v) {
            this._setStdModContent("body", v);
        },
        _uiSetHeaderContent:function(v) {
            this._setStdModContent("header", v);
        },
        _uiSetFooterContent:function(v) {
            this._setStdModContent("footer", v);
        },
        __renderUI:function() {
            renderUI(this, "header");
            renderUI(this, "body");
            renderUI(this, "footer");
        }
    };

    return StdMod;

}, {
    requires:['node']
});/**
 * @fileOverview uibase
 * @author yiminghe@gmail.com
 */
KISSY.add("uibase", function(S, UIBase, Align, Box, BoxRender, Close, CloseRender, Contrain, Contentbox, ContentboxRender, Drag, Loading, LoadingRender, Mask, MaskRender, Position, PositionRender, ShimRender, Resize, StdMod, StdModRender) {
    Close.Render = CloseRender;
    Loading.Render = LoadingRender;
    Mask.Render = MaskRender;
    Position.Render = PositionRender;
    StdMod.Render = StdModRender;
    Box.Render = BoxRender;
    Contentbox.Render = ContentboxRender;
    S.mix(UIBase, {
        Align:Align,
        Box:Box,
        Close:Close,
        Contrain:Contrain,
        Contentbox:Contentbox,
        Drag:Drag,
        Loading:Loading,
        Mask:Mask,
        Position:Position,
        Shim:{
            Render:ShimRender
        },
        Resize:Resize,
        StdMod:StdMod
    });
    return UIBase;
}, {
    requires:["uibase/base",
        "uibase/align",
        "uibase/box",
        "uibase/boxrender",
        "uibase/close",
        "uibase/closerender",
        "uibase/constrain",
        "uibase/contentbox",
        "uibase/contentboxrender",
        "uibase/drag",
        "uibase/loading",
        "uibase/loadingrender",
        "uibase/mask",
        "uibase/maskrender",
        "uibase/position",
        "uibase/positionrender",
        "uibase/shimrender",
        "uibase/resize",
        "uibase/stdmod",
        "uibase/stdmodrender"]
});
