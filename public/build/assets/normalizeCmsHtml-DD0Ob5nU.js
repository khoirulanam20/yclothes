function r(e){return e&&e.replace(/(?:\.\.\/)+storage\/([^"'>\s]+)/gi,"/storage/$1").replace(new RegExp(`(?<=["'])storage\\/([^"'>\\s]+)`,"gi"),"/storage/$1")}export{r as n};
