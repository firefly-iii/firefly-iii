// @flow

import * as React from "react";
import cn from "classnames";
import { Button } from "tabler-react";
import "./ComponentDemo.css";
import { Prism as SyntaxHighlighter } from "react-syntax-highlighter";
import { prism } from "react-syntax-highlighter/dist/styles/prism";
import reactElementToJSXString from "./react-element-to-jsx-string";

type Props = {|
  +children: React.Element<any>,
  +className?: string,
  +asString?: string,
|};

type State = {|
  codeOpen: boolean,
|};

class ComponentDemo extends React.PureComponent<Props, State> {
  state = {
    codeOpen: false,
  };
  handleSourceButtonOnClick = (e: SyntheticMouseEvent<HTMLInputElement>) => {
    e.preventDefault();
    this.setState(s => ({ codeOpen: !s.codeOpen }));
  };

  render() {
    const { className, children, asString } = this.props;
    const { codeOpen } = this.state;
    const classes = cn("ComponentDemo", className);
    return (
      <div className={classes}>
        <Button
          onClick={this.handleSourceButtonOnClick}
          size="sm"
          color="primary"
          outline
          className="viewSourceBtn"
        >
          {codeOpen ? "Close" : "Source"}
        </Button>
        <div className="example">{children}</div>
        {codeOpen && (
          <div className="highlight">
            <SyntaxHighlighter language="jsx" style={prism}>
              {asString || reactElementToJSXString(children)}
            </SyntaxHighlighter>
          </div>
        )}
      </div>
    );
  }
}

export default ComponentDemo;
