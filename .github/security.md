# Security Policy

Firefly III is an application to manage your personal finances. As such, the developer has adopted this security disclosure and response policy to ensure that critical issues are responsibly handled.

## Supported Versions
Only the latest Firefly III release is maintained. Applicable fixes, including security fixes, will not backported to older release branches. Please refer to [releases.md](https://github.com/firefly-iii/firefly-iii/blob/main/releases.md) for details.

## Reporting a Vulnerability - Private Disclosure Process
Security is of the highest importance and all security vulnerabilities or suspected security vulnerabilities should be reported to Firefly III privately, to minimize attacks against current users of Firefly III before they are fixed. Vulnerabilities will be investigated and patched on the next patch (or minor) release as soon as possible. This information could be kept entirely internal to the project.  

If you know of a publicly disclosed security vulnerability for Firefly III, please **IMMEDIATELY** contact james@firefly-iii.org to inform the Firefly III developer. You can use my [GPG key](https://keybase.io/jc5) for extra security.

**IMPORTANT: Do not file public issues on GitHub for security vulnerabilities**

To report a vulnerability or a security-related issue, please email the private address james@firefly-iii.org with the details of the vulnerability. The email will be received by the developer of Firefly III. Emails will be addressed within 3 business days, including a detailed plan to investigate the issue and any potential workarounds to perform in the meantime. Do not report non-security-impacting bugs through this channel. Use [GitHub issues](https://github.com/firefly-iii/firefly-iii/issues/new/choose) instead.

### Proposed Email Content
Provide a descriptive subject line and in the body of the email include the following information:
* Basic identity information, such as your name and your affiliation or company.
* Detailed steps to reproduce the vulnerability  (POC scripts, screenshots, and compressed packet captures are all helpful to us).
* Description of the effects of the vulnerability on Firefly III and the related hardware and software configurations, so that the developer can reproduce it.
* How the vulnerability affects Firefly III usage and an estimation of the attack surface, if there is one.
* List other projects or dependencies that were used in conjunction with Firefly III to produce the vulnerability.

## When to report a vulnerability
* When you think Firefly III has a potential security vulnerability.
* When you suspect a potential vulnerability but you are unsure that it impacts Firefly III.
* When you know of or suspect a potential vulnerability on another project that is used by Firefly III. For example Firefly III has a dependency on Docker, MySQL, etc.
  
## Patch, Release, and Disclosure
The Firefly III developer will respond to vulnerability reports as follows:
 
1.  The developer will investigate the vulnerability and determine its effects and criticality.
2.  If the issue is not deemed to be a vulnerability, the developer will follow up with a detailed reason for rejection.
3.  The developer will initiate a conversation with the reporter within 3 business days.
4.  If a vulnerability is acknowledged and the timeline for a fix is determined, the developer will work on a plan to communicate with the appropriate community, including identifying mitigating steps that affected users can take to protect themselves until the fix is rolled out.
5.  The developer will also create a [CVSS](https://www.first.org/cvss/specification-document) using the [CVSS Calculator](https://www.first.org/cvss/calculator/3.0). The developer makes the final call on the calculated CVSS; it is better to move quickly than making the CVSS perfect. Issues may also be reported to [Mitre](https://cve.mitre.org/) using this [scoring calculator](https://nvd.nist.gov/vuln-metrics/cvss/v3-calculator). The CVE will initially be set to private.
6.  The developer will work on fixing the vulnerability and perform internal testing before preparing to roll out the fix.
7. A public disclosure date is negotiated by the Firefly III developer and the bug submitter. We prefer to fully disclose the bug as soon as possible once a user mitigation or patch is available. It is reasonable to delay disclosure when the bug or the fix is not yet fully understood, the solution is not well-tested, or for distributor coordination. The timeframe for disclosure is from immediate (especially if it’s already publicly known) to a few weeks. For a critical vulnerability with a straightforward mitigation, we expect report date to public disclosure date to be on the order of 14 business days. The Firefly III developer holds the final say when setting a public disclosure date.
9.  Once the fix is confirmed, the developer will patch the vulnerability in the next patch or minor release. Upon release of the patched version of Firefly III, we will follow the **Public Disclosure Process**.

### Public Disclosure Process
The developer publishes a public [advisory](https://github.com/firefly-iii/firefly-iii/security/advisories) to the Firefly III community via GitHub. In most cases, additional communication via Twitter, reddit and other channels will assist in educating Firefly III users and rolling out the patched release to affected users. 

The develop will also publish any mitigating steps users can take until the fix can be applied to their Firefly III instances.
 
## Confidentiality, integrity and availability
We consider vulnerabilities leading to the compromise of data confidentiality, elevation of privilege, or integrity to be our highest priority concerns. Availability, in particular in areas relating to DoS and resource exhaustion, is also a serious security concern. The Firefly III developer takes all vulnerabilities, potential vulnerabilities, and suspected vulnerabilities seriously and will investigate them in an urgent and expeditious manner.

Note that we do not currently consider the default settings for Firefly III to be secure-by-default. It is necessary for operators to explicitly configure settings, role based access control, and other resource related features in Firefly III to provide a hardened Firefly III environment. We will not act on any security disclosure that relates to a lack of safe defaults. Over time, we will work towards improved safe-by-default configuration, taking into account backwards compatibility.

## Credits

This security policy is based on [Harbor](https://github.com/goharbor/harbor)'s security policy.
