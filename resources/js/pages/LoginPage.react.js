// @flow

import * as React from "react";
import { Formik } from "formik";
import { LoginPage as TablerLoginPage } from "tabler-react";

type Props = {||};

function LoginPage(props: Props): React.Node {
  return (
    <Formik
      initialValues={{
        email: "",
        password: "",
      }}
      validate={values => {
        // same as above, but feel free to move this into a class method now.
        let errors = {};
        if (!values.email) {
          errors.email = "Required";
        } else if (
          !/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i.test(values.email)
        ) {
          errors.email = "Invalid email address";
        }
        return errors;
      }}
      onSubmit={(
        values,
        { setSubmitting, setErrors /* setValues and other goodies */ }
      ) => {
        alert("Done!");
      }}
      render={({
        values,
        errors,
        touched,
        handleChange,
        handleBlur,
        handleSubmit,
        isSubmitting,
      }) => (
        <TablerLoginPage
          onSubmit={handleSubmit}
          onChange={handleChange}
          onBlur={handleBlur}
          values={values}
          errors={errors}
          touched={touched}
        />
      )}
    />
  );
}

export default LoginPage;
