/* ==================================================
 * Accela - PHP Web Framework for SPA
 * ================================================== */

(async () => {

/* ==================================================
 * Selectors
 * ================================================== */

const SEL = {
  IF: "template[a-if]",
  FOR: "template[a-for]",
  COMPONENT: "a-c, accela-component, template[a-c]",
  SLOT: "a-slot, template[a-slot]"
};

const MAX_COMPONENT_DEPTH = 1000;

// 属性値を取得するヘルパー
const getIfCond = (el) => el.getAttribute("a-if");
const getForExpr = (el) => el.getAttribute("a-for");
const getComponentName = (el) => el.getAttribute("use") || el.getAttribute("a-c");
const getSlotName = (el) => el.getAttribute("name") || el.getAttribute("a-slot") || "default";

// template の中身を取得
const getTemplateInnerHTML = (el) => {
  if (el.tagName === "TEMPLATE") {
    const div = document.createElement("div");
    div.append(...el.content.cloneNode(true).childNodes);
    return div.innerHTML;
  }
  return el.innerHTML;
};

// コンポーネントタグかどうか判定
const isComponentElement = (el) => {
  const tag = el.tagName;
  if (tag === "A-C" || tag === "ACCELA-COMPONENT") return true;
  if (tag === "TEMPLATE" && el.hasAttribute("a-c")) return true;
  return false;
};

/* ==================================================
 * Errors
 * ================================================== */

class ClientError extends Error {
  static {
    this.prototype.name = "ClientError";
    this.prototype.isServerError = false;
  }
}

class ComponentNotFoundError extends ClientError {
  static {
    this.prototype.name = "ComponentNotFoundError";
  }

  constructor(componentName) {
    super();
    this.componentName = componentName;
    this.references = [
      ["コンポーネント", "https://accela.in-green-spot.com/document/components/"]
    ];
  }
}

class ModuleNotFoundError extends ClientError {
  static {
    this.prototype.name = "ModuleNotFoundError";
  }

  constructor(moduleName) {
    super();
    this.moduleName = moduleName;
    this.references = [
      ["モジュール", "https://accela.in-green-spot.com/document/modules/"]
    ];
  }
}

/* ==================================================
 * Error View
 * ================================================== */

const viewError = (error) => {
  const $c = (tag, fn) => {
    const e = document.createElement(tag);
    if (fn) fn(e);
    return e;
  };
  const $a = (p, c) => p.appendChild(c);

  $a(document.querySelector("head"), $c("style", (style) => {
    style.textContent = '*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}body{margin:0 !important;padding:0 !important;}#accela-error{display:flex;justify-content:center;align-items:flex-start;width:100%;min-height:100vh;background-color:#363E47}.accela-error{width:100%;max-width:880px;margin:80px 40px;background-color:#fff;h1{padding:0 30px;color:#fff;font-size:30px;font-weight:400;line-height:60px;background-color:#BE202E}>.body{display:flex;flex-direction:column;gap:20px;padding:40px 30px;font-size:18px;line-height:1.5;p:first-child{&::before{content:"⚠️ "}span{color:#BE202E}}.references{padding:20px;border-radius:5px;background-color:#E7EEF6;h2{font-size:22px;font-weight:400;line-height:1em}ul{display:flex;flex-direction:column;gap:10px;margin-top:20px;list-style:none;li{position:relative;padding-left:15px;font-size:18px;&::before{content:"";position:absolute;top:10px;left:0;border-left:6px solid #1F5BAA;border-top:4px solid transparent;border-bottom:4px solid transparent;border-right:none}a{text-decoration:none;color:#1F5BAA}span{display:block;color:#888;font-size:14px}}}}.trace{display:flex;flex-direction:column;gap:20px;padding:20px;border-radius:5px;background-color:#363E47;h2{color:#fff;font-size:22px;font-weight:400;line-height:1em}pre{margin:0 -20px -20px;padding:0 20px 20px;overflow-x:auto;color:#fff;}ul{display:flex;flex-direction:column;gap:10px;list-style:none;li{padding:10px 15px;border-radius:5px;overflow:auto;background-color:#fff;>span{display:block}span.args{font-size:14px}span.path{white-space:nowrap;color:#888;font-size:14px}}}}}}';
  }));

  const wrapper = $c("section", (w) => {
    w.className = "accela-error";

    $a(w, $c("h1", (h1) => h1.textContent = error.isServerError ? "Server-side Exception" : "Client-side Exception"));

    $a(w, $c("div", (body) => {
      body.className = "body";

      if (error.isServerError) {
        switch (error.name) {
          case "NoPagePathsError":
            $a(body, $c("p", (p) => {
              p.innerHTML = `<span>${error.pagePath}</span>のPage Pathsが指定されていません。`;
            }));
            break;

          case "ServerComponentDomainNotFoundError":
            $a(body, $c("p", (p) => {
              p.innerHTML = `名前空間<span>${error.domainName}</span>が存在しません。`;
            }));
            break;

          case "ServerComponentNotFoundError":
            $a(body, $c("p", (p) => {
              p.innerHTML = `サーバコンポーネント<span>${error.componentName}</span>が存在しません。`;
            }));
            break;

          default:
            $a(body, $c("h2", (h2) => h2.textContent = error.name));
            $a(body, $c("p", (p) => p.innerHTML = `${error.message}<br>in ${error.file} (Line ${error.line})`));
        }
      } else {
        switch (error.name) {
          case "ComponentNotFoundError":
            $a(body, $c("p", (p) => {
              p.innerHTML = `コンポーネント<span>${error.componentName}</span>が存在しません。`;
            }));
            break;

          case "ModuleNotFoundError":
            $a(body, $c("p", (p) => {
              p.innerHTML = `モジュール<span>${error.moduleName}</span>が存在しません。`;
            }));
            break;

          default:
            $a(body, $c("h2", (h2) => h2.textContent = error.name));
        }
      }

      if (error.references) {
        $a(body, $c("section", (div) => {
          div.className = "references";

          $a(div, $c("h2", (h2) => h2.textContent = "References"));
          $a(div, $c("ul", (ul) => {
            error.references.forEach(([title, url]) => {
              $a(ul, $c("li", (li) => {
                $a(li, $c("a", (a) => {
                  a.textContent = title;
                  a.href = url;
                  a.target = "_blank";
                }));

                $a(li, $c("span", (span) => span.textContent = `${url}`));
              }));
            });
          }));
        }));
      }

      $a(body, $c("section", (div) => {
        div.className = "trace";

        $a(div, $c("h2", (h2) => h2.textContent = "Trace"));
        if (error.stackTrace) {
          $a(div, $c("ul", (ul) => {
            error.stackTrace.forEach((stack) => {
              $a(ul, $c("li", (li) => {
                let line = `<span>${stack.class ?? ""}${stack.type ?? ""}${stack.function ?? ""}</span>`;
                if (stack.file) line += `<span class="path">in ${stack.file} (Line ${stack.line})</span>`;
                li.innerHTML = line;
              }));
            });
          }));
        } else {
          $a(div, $c("pre", (pre) => pre.innerHTML = error.stack));
        }
      }));
    }));
  });

  const body = document.getElementById("accela");
  body.id = "accela-error";
  $a(body, wrapper);
};

/* ==================================================
 * Utils
 * ================================================== */

// ドット記法でネストした値を取得（ホワイトリスト付き）
const getValue = (obj, path) => {
  if (!obj || !path) return undefined;

  const builtinWhitelist = ["length"];

  return path.split(".").reduce((o, k) => {
    if (o == null) return undefined;

    if (Object.hasOwn(o, k) || builtinWhitelist.includes(k)) {
      return o[k];
    }

    return undefined;
  }, obj);
};

// props または globalProps から値を取得
const resolveValue = (props, path) => {
  let val = getValue(props, path);
  if (typeof val === "undefined") {
    val = getValue(ACCELA.globalProps, path);
  }
  return val;
};

/* ==================================================
 * Binding
 * ================================================== */

// propsを要素にバインド（再帰的、バインド後に属性を削除）
const bindProps = (element, props) => {
  if (typeof props === "undefined") {
    throw new Error("props is undefined");
  }

  // data-bind
  if (element.hasAttribute("data-bind")) {
    const bindAttr = element.getAttribute("data-bind");
    element.removeAttribute("data-bind");

    bindAttr.split(",").forEach(set => {
      let [prop, variable] = set.split(":");
      if (!variable) variable = prop;

      const val = resolveValue(props, variable);
      if (typeof val !== "undefined") {
        element.setAttribute(prop, typeof val === "string" ? val : JSON.stringify(val));
      }
    });
  }

  // data-bind-html
  if (element.hasAttribute("data-bind-html")) {
    const variable = element.getAttribute("data-bind-html");
    element.removeAttribute("data-bind-html");

    const html = resolveValue(props, variable);
    if (typeof html !== "undefined") {
      element.innerHTML = html;
    }
  }

  // data-bind-text
  if (element.hasAttribute("data-bind-text")) {
    const variable = element.getAttribute("data-bind-text");
    element.removeAttribute("data-bind-text");

    const text = resolveValue(props, variable);
    if (typeof text !== "undefined") {
      element.textContent = text;
    }
  }

  // 子要素を再帰的に処理
  if (element.children) {
    Array.from(element.children).forEach(child => {
      // template[a-for]とa-cは新しいスコープなのでスキップ
      if (child.tagName === 'TEMPLATE' && child.hasAttribute('a-for')) {
        return;
      }
      if (isComponentElement(child)) {
        return;
      }
      bindProps(child, props);
    });
  }
};

/* ==================================================
 * Control Structures (if / for)
 * ================================================== */

// 条件式を評価
const evalCond = (expr, props) => {
  expr = expr.trim();

  // !variable (否定)
  if (expr.startsWith("!")) {
    return !resolveValue(props, expr.slice(1).trim());
  }

  // 比較演算子
  const match = expr.match(/^(.+?)\s*(==|!=|>=|<=|>|<)\s*(.+)$/);
  if (match) {
    const left = resolveValue(props, match[1].trim());
    const op = match[2];
    const rightRaw = match[3].trim();

    let right;
    if (/^['"].*['"]$/.test(rightRaw)) {
      right = rightRaw.slice(1, -1);
    } else if (/^-?\d+(\.\d+)?$/.test(rightRaw)) {
      right = Number(rightRaw);
    } else {
      right = resolveValue(props, rightRaw);
    }

    switch (op) {
      case "==": return left == right;
      case "!=": return left != right;
      case ">":  return Number(left) > Number(right);
      case ">=": return Number(left) >= Number(right);
      case "<":  return Number(left) < Number(right);
      case "<=": return Number(left) <= Number(right);
    }
  }

  // 単純な truthy チェック
  return !!resolveValue(props, expr);
};

// 統一処理関数: if → for → component の順で実行（bindはしない）
const processContent = (content, props, components = null, slotProps = null, depth = 0) => {
  if (depth >= MAX_COMPONENT_DEPTH) throw new Error("Maximum recursion depth exceeded");

  // 1. if/for を処理
  applyIf(content, props);
  applyFor(content, props, components, slotProps, depth);

  // 2. コンポーネントを展開
  if (components) {
    const expandComponents = (el) => {
      const children = Array.from(el.children);
      for (const child of children) {
        if (isComponentElement(child)) {
          applyComponents(child, components, props, slotProps || props, depth);
        } else {
          expandComponents(child);
        }
      }
    };
    expandComponents(content);
  }
};

// a-if
const applyIf = (content, props) => {
  let el;
  while ((el = content.querySelector(SEL.IF))) {
    const cond = getIfCond(el);

    if (evalCond(cond, props)) {
      const fragment = document.createDocumentFragment();
      fragment.append(...el.content.cloneNode(true).childNodes);
      el.replaceWith(fragment);
    } else {
      el.remove();
    }
  }
};

// a-for
const applyFor = (content, props, components = null, slotProps = null, depth = 0) => {
  let el;
  while ((el = content.querySelector(SEL.FOR))) {
    const forAttr = getForExpr(el);
    const match = forAttr.match(/^(\w+)(?:\s*,\s*(\w+))?\s+in\s+(.+)$/);

    if (!match) {
      el.remove();
      continue;
    }

    const itemName = match[1];
    const indexName = match[2];
    const itemsKey = match[3].trim();
    const items = resolveValue(props, itemsKey) || [];

    const templateHTML = getTemplateInnerHTML(el);
    const fragment = document.createDocumentFragment();

    Object.entries(items).forEach(([index, item]) => {
      const loopProps = { ...props, [itemName]: item };
      if (indexName) loopProps[indexName] = index;

      const div = document.createElement("div");
      div.innerHTML = templateHTML;

      processContent(div, loopProps, components, slotProps, depth);
      bindProps(div, loopProps);

      fragment.append(...div.childNodes);
    });

    el.replaceWith(fragment);
  }
};

/* ==================================================
 * Components
 * ================================================== */

const applyComponents = (content, components, props = {}, slotProps = null, depth = 0) => {
  const effectiveSlotProps = slotProps ?? props;

  if (isComponentElement(content)) {
    // コンポーネント名を取得（@use 対応）
    let componentName = getComponentName(content);
    const dynamicUse = content.getAttribute("@use");
    if (dynamicUse) {
      componentName = getValue(props, dynamicUse) || getValue(ACCELA.globalProps || {}, dynamicUse);
      if (!componentName) {
        console.warn(`@use="${dynamicUse}" resolved to empty`);
        return;
      }
    }

    const component = components[componentName];
    if (!component) {
      throw new ComponentNotFoundError(componentName);
    }

    const componentProps = {};

    // props を収集（型変換付き）
    content.getAttributeNames().forEach(propName => {
      const propValue = content.getAttribute(propName);

      // template 用の属性と @use はスキップ
      if (propName === "a-c" || propName === "@use") return;

      if (propName[0] === "@") {
        const key = propName.slice(1);
        // ドット記法に対応するため resolveValue を使用
        const resolved = resolveValue(props, propValue);
        if (typeof resolved !== "undefined") {
          componentProps[key] = resolved;
        } else if (propValue in (ACCELA.globalProps || {})) {
          componentProps[key] = ACCELA.globalProps[propValue];
        }
      } else if (propName !== "use") {
        // 静的 props は型変換
        let v = propValue;
        if (v === "true") v = true;
        else if (v === "false") v = false;
        else if (/^-?\d+(\.\d+)?$/.test(v)) v = Number(v);
        componentProps[propName] = v;
      }
    });

    const componentObject = component.object.cloneNode(true);

    // slot コンテンツを収集
    const slotContents = {};
    const contentHTML = getTemplateInnerHTML(content);
    const tempDiv = document.createElement("div");
    tempDiv.innerHTML = contentHTML;

    // 名前付き slot を収集
    tempDiv.querySelectorAll(SEL.SLOT).forEach(slotEl => {
      const slotName = getSlotName(slotEl);
      slotContents[slotName] = getTemplateInnerHTML(slotEl);
      slotEl.remove();
    });

    // 残りは default slot
    if (tempDiv.innerHTML.trim()) {
      slotContents["default"] = (slotContents["default"] || "") + tempDiv.innerHTML;
    }

    // slot を適用
    componentObject.querySelectorAll("[data-slot]").forEach(slotTarget => {
      const slotName = slotTarget.getAttribute("data-slot") || "default";
      if (slotContents[slotName]) {
        slotTarget.innerHTML = slotContents[slotName];
        // slot 内は effectiveSlotProps で processContent を使用
        processContent(slotTarget, effectiveSlotProps, components, effectiveSlotProps, depth + 1);
        bindProps(slotTarget, effectiveSlotProps);
      }
    });

    // 従来の data-contents も対応（後方互換）
    componentObject.querySelectorAll(`[data-contents="${componentName}"]`).forEach(_o => {
      const defaultContent = slotContents["default"] || "";
      _o.innerHTML = defaultContent;
      // data-contents 内は effectiveSlotProps で processContent を使用
      processContent(_o, effectiveSlotProps, components, effectiveSlotProps, depth + 1);
      bindProps(_o, effectiveSlotProps);
    });

    // コンポーネント本体を processContent で処理
    // data-contents と data-slot は既に処理済みなのでスキップ
    const children = Array.from(componentObject.children);
    for (const child of children) {
      if (child.hasAttribute("data-contents") || child.hasAttribute("data-slot")) {
        continue;
      }
      processContent(child, componentProps, components, effectiveSlotProps, depth + 1);
      bindProps(child, componentProps);
    }

    // 置換
    const firstChild = componentObject.firstElementChild;
    if (firstChild) {
      content.replaceWith(firstChild);
    } else {
      content.remove();
    }

  } else {
    content.querySelectorAll(":scope > *").forEach(_o => {
      applyComponents(_o, components, props, effectiveSlotProps, depth + 1);
    });
  }
};

/* ==================================================
 * Modules
 * ================================================== */

const applyModules = (contents, depth = 0) => {
  if (depth >= MAX_COMPONENT_DEPTH) throw new Error("Maximum nesting depth exceeded");

  contents.querySelectorAll(":scope > *").forEach(o => {
    applyModules(o, depth + 1);
  });

  const moduleName = contents.getAttribute("data-use-module");
  if (moduleName) {
    if (ACCELA.modules[moduleName]) {
      ACCELA.modules[moduleName](contents);
    } else {
      throw new ModuleNotFoundError(moduleName);
    }
  }
};

/* ==================================================
 * Page Classes
 * ================================================== */

class Page {
  constructor(page) {
    this.path = page.path;
    this.head = new PageHead(page.path, page.head, page.props);
    this.content = new PageContent(page.path, page.content, page.props);
  }
}

class PageHead {
  constructor(path, head, props) {
    this.object = (() => {
      const o = document.createElement("accela:head");
      o.innerHTML = head;
      return o;
    })();

    this.props = props;
    bindProps(this.object, props);
  }

  html() {
    return this.object.innerHTML;
  }
}

class PageContent {
  constructor(path, content, props) {
    this.object = (() => {
      const o = document.createElement("accela:content");
      o.innerHTML = content;
      return o;
    })();

    this.path = path;
    this.props = props;
  }

  html() {
    const contents = this.object.cloneNode(true);
    applyModules(contents);
    return contents;
  }

  applyComponents(components) {
    processContent(this.object, this.props, components, this.props);
    bindProps(this.object, this.props);
  }
}

class Component {
  constructor(name, component) {
    this.object = (() => {
      const o = document.createElement("accela:component");
      o.setAttribute("data-name", name);
      o.innerHTML = component;
      const contentsArea = o.querySelector("[data-contents]");
      if (contentsArea) contentsArea.setAttribute("data-contents", name);
      return o;
    })();
  }
}

/* ==================================================
 * App (メイン処理)
 * ================================================== */

const start = async function() {
  ACCELA._movePage = (path) => location.href = path;

  const head = document.querySelector("head");
  const body = document.getElementById("accela");

  const components = {};
  Object.entries(ACCELA.components).forEach(([name, component]) => {
    components[name] = new Component(name, component);
  });

  const beforeMovePage = () => {
    ACCELA.hooks.beforeMovePage();
  };

  const afterMovePage = () => {
    ACCELA.hooks.afterMovePage();
  };

  const movePage = (page, hash, isFirst) => {
    if (!ACCELA.changePageContent) {
      ACCELA.changePageContent = (body, pageContent) => {
        body.innerHTML = "";
        body.appendChild(pageContent);
      };
    }

    const pageContent = page.content.html();

    const move = () => {
      document.querySelectorAll("html, body").forEach(o => {
        o.scrollTop = 0;
      });

      const tags = head.querySelectorAll("*");
      (() => {
        let isDynamicTags = false;

        tags.forEach(o => {
          if (o.getAttribute("name") === "accela-separator") {
            isDynamicTags = true;
            return;
          }
          if (!isDynamicTags) return;
          o.remove();
        });
      })();

      ((div) => {
        div.innerHTML = page.head.html();
        div.querySelectorAll(":scope > *").forEach(o => {
          if (o.tagName === "TITLE" && head.querySelector("title")) {
            head.querySelector("title").textContent = o.textContent;

          } else if (o.tagName === "META") {
            const name = o.getAttribute("name");
            const property = o.getAttribute("property");
            const meta = (() => {
              if (name) return head.querySelector(`meta[name="${name}"]`);
              if (property) return head.querySelector(`meta[property="${property}"]`);
              return false;
            })();

            if (meta) {
              head.replaceChild(o, meta);
            } else {
              head.appendChild(o);
            }

          } else {
            head.appendChild(o);
          }
        });
      })(document.createElement("div"));

      ACCELA.changePageContent(body, pageContent.querySelector(":scope > *"));
      body.setAttribute("data-page-path", page.path);
    };

    if (isFirst) {
      if (ACCELA.initPage) ACCELA.initPage();
      move();
    } else {
      beforeMovePage();
      ACCELA.movePage ? ACCELA.movePage(pageContent, move) : move();
      afterMovePage();
    }

    ACCELA.changeHash ? ACCELA.changeHash(hash) : setTimeout(() => location.hash = hash, 100);
  };

  const site = {};

  const firstPage = new Page(ACCELA.entrancePage);
  firstPage.content.applyComponents(components);

  movePage(firstPage, location.hash, true);

  const res = await fetch(`/assets/site.json?__t=${ACCELA.utime}`);

  Object.entries(await res.json()).forEach(([path, _page]) => {
    const page = new Page(_page);
    page.content.applyComponents(components);
    site[path] = page;
  });

  document.querySelector("body").addEventListener("click", e => {
    let target = e.target;
    if (target.tagName !== "A") target = target.closest("a");

    if (!target) return true;
    if (e.metaKey || e.shiftKey || e.altKey) return true;

    const href = target.getAttribute("href");
    if (!href) return true;

    const url = new URL(href, location.href);
    const path = url.pathname;
    if (url.hostname !== location.hostname || !site[path]) return true;

    e.preventDefault();
    if (path === location.pathname) {
      ACCELA.changeHash ? ACCELA.changeHash(url.hash) : (() => location.hash = url.hash)();
      return false;
    }

    const prevLocation = { href: location.href, pathname: location.pathname, search: location.search };
    history.pushState({ prev: prevLocation }, null, path + url.search);
    location.prev = prevLocation;
    movePage(site[path], url.hash);

    return false;
  });

  window.addEventListener("popstate", (e) => {
    if (e.originalEvent && !e.originalEvent.state) return;

    try {
      location.prev = e.state?.prev;
      movePage(site[location.pathname], location.hash);
    } catch (error) {
      viewError(error);
    }
  });

  ACCELA._movePage = (path) => {
    try {
      const prevLocation = { href: location.href, pathname: location.pathname, search: location.search };
      history.pushState({ prev: prevLocation }, null, path);
      location.prev = prevLocation;
      movePage(site[path], "");
    } catch (error) {
      viewError(error);
    }
  };
};

/* ==================================================
 * Entry Point
 * ================================================== */

if (ACCELA.serverError) {
  viewError(ACCELA.serverError);
} else {
  start();
}

})();