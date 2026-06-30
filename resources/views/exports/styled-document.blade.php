<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title }}</title>
<style>
  :root {
    --text: #4a3a28;
    --text-2: #9f927d;
    --accent: #19c8b9;
    --border: #e8e2d6;
    --paper: #fffdf8;
    --bg: #f6f1e7;
    --code-bg: #f3efe3;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    padding: 40px 16px 64px;
    background: var(--bg);
    color: var(--text);
    font-family: "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", system-ui, sans-serif;
    font-size: 16px;
    line-height: 1.85;
    -webkit-font-smoothing: antialiased;
  }
  article {
    max-width: 800px;
    margin: 0 auto;
    padding: 48px 56px 56px;
    background: var(--paper);
    border: 2px solid var(--border);
    border-radius: 24px;
  }
  h1 {
    font-size: 27px;
    line-height: 1.4;
    margin: 0 0 1em;
    padding-bottom: .55em;
    border-bottom: 2px solid var(--border);
  }
  h2 {
    font-size: 21px;
    margin: 2.4em 0 .9em;
    padding-left: 12px;
    border-left: 5px solid var(--accent);
    border-radius: 2px;
  }
  h3 {
    font-size: 17.5px;
    margin: 1.9em 0 .6em;
  }
  p { margin: .8em 0; }
  ul, ol { margin: .8em 0; padding-left: 1.5em; }
  li { margin: .45em 0; }
  li > ul, li > ol { margin: .3em 0; }
  blockquote {
    margin: 1em 0;
    padding: 14px 20px;
    background: #f2faf8;
    border-left: 4px solid var(--accent);
    border-radius: 0 12px 12px 0;
  }
  blockquote p { margin: .4em 0; }
  table {
    width: 100%;
    margin: 1.2em 0;
    border-collapse: collapse;
    font-size: 15px;
  }
  th, td {
    padding: 10px 16px;
    border: 1px solid var(--border);
    text-align: left;
  }
  th { background: #f3eee0; font-weight: 700; }
  tr:nth-child(even) td { background: #fbf8f0; }
  code {
    padding: 2px 7px;
    background: var(--code-bg);
    color: #8a5a2b;
    border-radius: 6px;
    font-size: .88em;
    font-family: "SF Mono", Menlo, Consolas, monospace;
  }
  pre {
    padding: 16px 20px;
    background: var(--code-bg);
    border-radius: 12px;
    overflow-x: auto;
    line-height: 1.6;
  }
  pre code { padding: 0; background: none; }
  hr {
    margin: 2.5em 0;
    border: none;
    border-top: 2px dashed var(--border);
  }
  a { color: var(--accent); }
  input[type="checkbox"] {
    margin-right: 6px;
    accent-color: var(--accent);
  }
  @media (max-width: 720px) {
    body { padding: 16px 8px 40px; }
    article { padding: 28px 22px 36px; border-radius: 16px; }
  }
  @page {
    size: A4;
    margin: 16mm 14mm;
  }
  @media print {
    /* 强制保留配色（默认打印会丢掉背景色与隔行底色） */
    * {
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    body {
      background: #fff;
      padding: 0;
      font-size: 12pt;
      line-height: 1.7;
    }
    article {
      max-width: none;
      margin: 0;
      padding: 0;
      border: none;
      border-radius: 0;
      background: none;
    }
    /* 分页友好：标题不与下文割裂、整块不被截断 */
    h1, h2, h3 { break-after: avoid; }
    table, blockquote, pre, tr, li { break-inside: avoid; }
    thead { display: table-header-group; }
    a { color: inherit; text-decoration: none; }
  }
</style>
</head>
<body>
<article>
{!! $body !!}
</article>
</body>
</html>
