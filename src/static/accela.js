/* --------------------------------------------------
 * Accela
 */

(async () => {
  const viewError = (error) => {
    const $c = (tag, fn) => {
      const e = document.createElement(tag);
      if(fn) fn(e);
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

        if(error.isServerError){
          // Server-side Exception
          switch(error.name){
            case "NoPagePathsError":
              $a(body, $c("p", (p) => {
                p.innerHTML = `<span>${error.pagePath}</span>のPage Pathsが指定されていません。`;
              }));
              break;

            case "ServerComponentDomainNotFoundError":
              $a(body, $c("p", (p) => {
                p.innerHTML = `名前空間<span>${error.domainName}</span>が存在しません。`
              }));
              break;

            case "ServerComponentNotFoundError":
              $a(body, $c("p", (p) => {
                p.innerHTML = `サーバコンポーネント<span>${error.componentName}</span>が存在しません。`
              }));
              break;

            default:
              $a(body, $c("h2", (h2) => h2.textContent = error.name));
              $a(body, $c("p", (p) => p.innerHTML = `${error.message}<br>in ${error.file} (Line ${error.line})`));
          }

        }else{
          // Client-side Exeption
          switch(error.name){
            case "ComponentNotFoundError":
              $a(body, $c("p", (p) => {
                p.innerHTML = `コンポーネント<span>${error.componentName}</span>が存在しません。`;
              }));
              break;

            case "ModuleNotFoundError":
              $a(body, $c("p", (p) => {
                p.innerHTML = `モジュール<span>${error.moduleName}</span>が存在しません。`
              }));
              break;

            default:
              $a(body, $c("h2", (h2) => h2.textContent = error.name));
          }
        }

        if(error.references){
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

          $a(div, $c("h2", (h2)=> h2.textContent = "Trace"));
          if(error.stackTrace){
            $a(div, $c("ul", (ul) => {
              const argsString = (args) => {
                args = args.map((arg) => {
                  switch(typeof arg){
                    case "string":
                      return `"${arg}"`;
                    case "number":
                      return arg;
                    default:
                      return arg;
                  }
                });
                return args.join(", ");
              };

              error.stackTrace.forEach((stack) => {
                $a(ul, $c("li", (li) => {
                  let line = `<span>${stack.class??""}${stack.type??""}${stack.function??""}</span>`
                  if(stack.file) line += `<span class="path">in ${stack.file} (Line ${stack.line})</span>`;
                  li.innerHTML = line;
                }));
              });
            }));
          }else{
            $a(div, $c("pre", (pre) => pre.innerHTML = error.stack));
          }
        }));
      }));
    });

    const body = document.getElementById("accela");
    body.id = "accela-error";
    $a(body, wrapper);
  };

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
      ]
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
      ]
    }
  }

  /*
  class PropertyNotFoundError extends ClientError {
    static {
      this.prototype.name = "PropertyNotFoundError";
    }

    constructor(propName) {
      super();
      this.propertyName = this.propertyName;
    }
  }
  */

  const start = async function(){
    ACCELA._movePage = (path) => location.href = path;

    const utils = {
      str2DOM: (str, wrapTagName="div") => {
        const dom = document.createElement(wrapTagName);
        dom.innerHTML = str;
        return dom;
      },

      bindProps: (content, props) => {
        if(typeof props === "undefined"){
          throw new Error("props is undefined");
        }

        content.querySelectorAll("[data-bind]").forEach(o => {
          o.getAttribute("data-bind").split(",").forEach(function(set){
            let [prop, variable] = set.split(":");
            if(!variable) variable = prop;

            const val = (() => {
              let val = props[variable];
              if(typeof val === "undefined") val = ACCELA.globalProps[variable];
              return typeof val === "string" ? val : JSON.stringify(val);
            })()
            o.setAttribute(prop, val);
          });
        });

        content.querySelectorAll("[data-bind-html]").forEach(o => {
          o.innerHTML = props[o.getAttribute("data-bind-html")];
        });

        content.querySelectorAll("[data-bind-text]").forEach(o => {
          o.textContent = props[o.getAttribute("data-bind-text")];
        });
      },

      applyComponents: (content, components, _props={}, depth=1) => {
        if(depth > 1000) throw new Error("error!");

        if(content.tagName === "ACCELA-COMPONENT"){
          const props = {};

          content.getAttributeNames().forEach(propName => {
            const prop = content.getAttribute(propName);
            if(propName[0] === "@" && _props[prop]){
              props[propName.slice[1]] = _props[propName];
            }else{
              props[propName] = content.getAttribute(propName);
            }
          });

          const componentName = content.getAttribute("use");
          const component = components[componentName];
          if(!component){
            throw new ComponentNotFoundError(componentName);
          }

          const componentObject = component.object.cloneNode(true);
          utils.bindProps(componentObject, props);

          componentObject.querySelectorAll(`[data-contents="${componentName}"]`).forEach(_o => {
            _o.innerHTML = content.innerHTML;
          });

          utils.applyComponents(componentObject, components, props, depth+1);
          content.parentNode.replaceChild(componentObject.firstElementChild, content);

        }else{
          content.querySelectorAll(":scope > *").forEach(_o => {
            utils.applyComponents(_o, components, _props, depth+1);
          });
        }

        return this;
      },

      applyModules: (contents, depth=1) => {
        if(depth > 1000) throw new Error("error!");

        contents.querySelectorAll(":scope > *").forEach(o => {
          utils.applyModules(o, depth+1);
        });

        const moduleName = contents.getAttribute("data-use-module");
        if(moduleName){
          if(ACCELA.modules[moduleName]){
            ACCELA.modules[moduleName](contents);
          }else{
            throw new ModuleNotFoundError(moduleName);
          }
        }
      },
    };

    class Page {
      constructor(page){
        this.path = page.path;
        this.head = new PageHead(page.path, page.head, page.props);
        this.content = new PageContent(page.path, page.content, page.props);
      }
    }

    class PageHead {
      constructor(path, head, props){
        this.object = (o => {
          o.innerHTML = head;
          return o;
        })(document.createElement("accela:head"));

        utils.bindProps(this.object, props);
      }

      html(){
        return this.object.innerHTML;
      }
    }

    class PageContent {
      constructor(path, content, props){
        this.object = (o => {
          o.innerHTML = content;
          return o;
        })(document.createElement("accela:content"));

        this.path = path;
        this.props = props;
        utils.bindProps(this.object, props);
      }

      html(){
        const contents = this.object.cloneNode(true);
        utils.applyModules(contents);
        return contents;
      }

      applyComponents(components){
        utils.applyComponents(this.object, components, this.props);
      }
    }

    class Component {
      constructor(name, component){
        this.object = (o => {
          o.setAttribute("data-name", name);
          o.innerHTML = component;
          const contentsArea = o.querySelector("[data-contents]");
          if(contentsArea) contentsArea.setAttribute("data-contents", name);

          return o;
        })(document.createElement("accela:component"));
      }
    }

    const head = document.querySelector("head"),
          body = document.getElementById("accela");

    const components = {};
    Object.entries(ACCELA.components).forEach(([name, component]) => {
      components[name] = new Component(name, component);
    });

    const beforeMovePage = () => {
      ACCELA.hooks.beforeMovePage();
    };

    const afterMovePage = () => {
      ACCELA.hooks.beforeMovePage();
    }

    const movePage = (page, hash, isFirst) => {
      if(!ACCELA.changePageContent){
        ACCELA.changePageContent = (body, pageContent) => {
          body.innerHTML = "";
          body.appendChild(pageContent);
        };
      }

      const pageContent = page.content.html();

      const move = () => {
        document.querySelectorAll("html, body").forEach(o => {
          o.scrollTop = 0;
        })

        const tags = head.querySelectorAll("*");
        (() => {
          let isDynamicTags = false;

          tags.forEach(o => {
            if(o.getAttribute("name") === "accela-separator"){
              isDynamicTags = true;
              return;
            }
            if(!isDynamicTags) return;
            o.remove();
          });
        })();

        ((div) => {
          div.innerHTML = page.head.html();
          div.querySelectorAll(":scope > *").forEach(o => {
            if(o.tagName === "TITLE" && head.querySelector("title")){
              // <title />は更新
              head.querySelector("title").textContent = o.textContent;

            }else if(o.tagName === "META"){
              // <meta />は、存在していたら更新
              const name = o.getAttribute("name");
              const property = o.getAttribute("property");
              const meta = (() => {
                if(name) return head.querySelector(`meta[name="${name}"]`);
                if(property) return head.querySelector(`meta[property="${property}"]`);
                return false;
              })();

              if(meta){
                head.replaceChild(o, meta);
              }else{
                head.appendChild(o);
              }

            }else{
              // その他のタグは追加
              head.appendChild(o);
            }
          });
        })(document.createElement("div"));

        ACCELA.changePageContent(body, pageContent.querySelector(":scope > *"));
        body.setAttribute("data-page-path", page.path);
      }

      if(isFirst){
        if(ACCELA.initPage) ACCELA.initPage();
        move();
      }else{
        beforeMovePage();
        ACCELA.movePage ? ACCELA.movePage(pageContent, move) : move();
        afterMovePage();
      }

      ACCELA.changeHash ? ACCELA.changeHash(hash) : setTimeout(() => location.hash = hash, 100);
    };

    const site = {};

//    try {
      const firstPage = new Page(ACCELA.entrancePage);
      firstPage.content.applyComponents(components);

      movePage(firstPage, location.hash, true);

      const res = await fetch(`/assets/site.json?__t=${ACCELA.utime}`);

      Object.entries(await res.json()).forEach(([path, _page]) => {
        const page = new Page(_page);
        page.content.applyComponents(components);
        site[path] = page;
      });
//    }catch(error){
//      viewError(error);
//      return;
//    }

    document.querySelector("body").addEventListener("click", e => {
      let target = e.target;
      if(target.tagName !== "A") target = target.closest("a");

      if(!target) return true;
      if(e.metaKey || e.shiftKey || e.altKey) return true;

      const url = new URL(target.getAttribute("href"), location.href);
      const path = url.pathname;
      if(url.hostname !== location.hostname || !site[path]) return true;

      e.preventDefault();
      if(path === location.pathname){
        ACCELA.changeHash ? ACCELA.changeHash(url.hash) : () => location.hash = url.hash;
        return false;
      }

      movePage(site[path], url.hash);
      history.pushState(null, null, path + url.search);

      return false;
    });

    window.onpopstate = (e) => {
      if(e.originalEvent && !e.originalEvent.state) return;

      try{
        movePage(site[location.pathname], location.hash);
      }catch(error){
        viewError(error);
      }
    };

    ACCELA._movePage = (path) => {
      try{
        movePage(site[path], "");
        history.pushState(null, null, path + url.search);
      }catch(error){
        viewError(error);
      }
    };
  };

  if(ACCELA.serverError){
    viewError(ACCELA.serverError);
  }else{
    start();
  }
})();
