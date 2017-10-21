@0xb0032512fca90cd9;

using Spk = import "/sandstorm/package.capnp";
# This imports:
#   $SANDSTORM_HOME/latest/usr/include/sandstorm/package.capnp
# Check out that file to see the full, documented package definition format.

const pkgdef :Spk.PackageDefinition = (
  # The package definition. Note that the spk tool looks specifically for the
  # "pkgdef" constant.

  id = "uws252ya9mep4t77tevn85333xzsgrpgth8q4y1rhknn1hammw70",
  # Your app ID is actually its public key. The private key was placed in
  # your keyring. All updates must be signed with the same key.

  manifest = (
    appTitle = (defaultText = "Firefly III"),
    appVersion = 1,
    appMarketingVersion = (defaultText = "4.6.9.1"),

    actions = [
      # Define your "new document" handlers here.
      ( nounPhrase = (defaultText = "administration"),
        command = .myCommand
        # The command to run when starting for the first time. (".myCommand"
        # is just a constant defined at the bottom of the file.)
      )
    ],

    continueCommand = .myCommand,
    # This is the command called to start your app back up after it has been
    # shut down for inactivity. Here we're using the same command as for
    # starting a new instance, but you could use different commands for each
    # case.

    metadata = (
         icons = (
        appGrid = (png = (dpi1x = embed "app-graphics/firefly-iii-128.png")),
        grain = (png = (dpi1x = embed "app-graphics/firefly-iii-24.png",
                        dpi2x = embed "app-graphics/firefly-iii-48.png")),
        market = (png = (dpi1x = embed "app-graphics/firefly-iii-150.png"))
      ),

      website = "https://firefly-iii.github.io/",
      codeUrl = "https://github.com/firefly-iii/firefly-iii",
      license = (openSource = gpl3),
      # The license this package is distributed under.  See
      # https://docs.sandstorm.io/en/latest/developing/publishing-apps/#license

      categories = [productivity],
      # A list of categories/genres to which this app belongs, sorted with best fit first.
      # See the list of categories at
      # https://docs.sandstorm.io/en/latest/developing/publishing-apps/#categories

      author = (
        contactEmail = "thegrumpydictator@gmail.com",
        upstreamAuthor = "James Cole",
        pgpSignature = embed "pgp-signature",
      ),

      pgpKeyring = embed "pgp-keyring",
      description = (defaultText = embed "description.md"),
      shortDescription = (defaultText = "Financial management"),
      screenshots = [
        # Screenshots to use for marketing purposes.  Examples below.
        # Sizes are given in device-independent pixels, so if you took these
        # screenshots on a Retina-style high DPI screen, divide each dimension by two.

        (width = 1291, height = 800, png = embed "screenshots/screenshot-1.png"),
        (width = 1291, height = 800, png = embed "screenshots/screenshot-2.png"),
        (width = 1291, height = 800, png = embed "screenshots/screenshot-3.png"),

      ],
      changeLog = (defaultText = embed "changelog.md"),
    ),
  ),

  sourceMap = (
    # Here we defined where to look for files to copy into your package. The
    # `spk dev` command actually figures out what files your app needs
    # automatically by running it on a FUSE filesystem. So, the mappings
    # here are only to tell it where to find files that the app wants.
    searchPath = [
      ( sourcePath = "." ),  # Search this directory first.
      ( sourcePath = "/",    # Then search the system root directory.
        hidePaths = [ "home", "proc", "sys",
                      "etc/passwd", "etc/hosts", "etc/host.conf",
                      "etc/nsswitch.conf", "etc/resolv.conf" ]
        # You probably don't want the app pulling files from these places,
        # so we hide them. Note that /dev, /var, and /tmp are implicitly
        # hidden because Sandstorm itself provides them.
      )
    ]
  ),

  fileList = "sandstorm-files.list",
  # `spk dev` will write a list of all the files your app uses to this file.
  # You should review it later, before shipping your app.

  alwaysInclude = ["opt/app/app","opt/app/bootstrap","opt/app/config","opt/app/database","opt/app/public","opt/app/resources","opt/app/routes","opt/app/vendor"],
  # Fill this list with more names of files or directories that should be
  # included in your package, even if not listed in sandstorm-files.list.
  # Use this to force-include stuff that you know you need but which may
  # not have been detected as a dependency during `spk dev`. If you list
  # a directory here, its entire contents will be included recursively.

  #bridgeConfig = (
  #  # Used for integrating permissions and roles into the Sandstorm shell
  #  # and for sandstorm-http-bridge to pass to your app.
  #  # Uncomment this block and adjust the permissions and roles to make
  #  # sense for your app.
  #  # For more information, see high-level documentation at
  #  # https://docs.sandstorm.io/en/latest/developing/auth/
  #  # and advanced details in the "BridgeConfig" section of
  #  # https://github.com/sandstorm-io/sandstorm/blob/master/src/sandstorm/package.capnp
  #  viewInfo = (
  #    # For details on the viewInfo field, consult "ViewInfo" in
  #    # https://github.com/sandstorm-io/sandstorm/blob/master/src/sandstorm/grain.capnp
  #
  #    permissions = [
  #    # Permissions which a user may or may not possess.  A user's current
  #    # permissions are passed to the app as a comma-separated list of `name`
  #    # fields in the X-Sandstorm-Permissions header with each request.
  #    #
  #    # IMPORTANT: only ever append to this list!  Reordering or removing fields
  #    # will change behavior and permissions for existing grains!  To deprecate a
  #    # permission, or for more information, see "PermissionDef" in
  #    # https://github.com/sandstorm-io/sandstorm/blob/master/src/sandstorm/grain.capnp
  #      (
  #        name = "editor",
  #        # Name of the permission, used as an identifier for the permission in cases where string
  #        # names are preferred.  Used in sandstorm-http-bridge's X-Sandstorm-Permissions HTTP header.
  #
  #        title = (defaultText = "editor"),
  #        # Display name of the permission, e.g. to display in a checklist of permissions
  #        # that may be assigned when sharing.
  #
  #        description = (defaultText = "grants ability to modify data"),
  #        # Prose describing what this role means, suitable for a tool tip or similar help text.
  #      ),
  #    ],
  #    roles = [
  #      # Roles are logical collections of permissions.  For instance, your app may have
  #      # a "viewer" role and an "editor" role
  #      (
  #        title = (defaultText = "editor"),
  #        # Name of the role.  Shown in the Sandstorm UI to indicate which users have which roles.
  #
  #        permissions  = [true],
  #        # An array indicating which permissions this role carries.
  #        # It should be the same length as the permissions array in
  #        # viewInfo, and the order of the lists must match.
  #
  #        verbPhrase = (defaultText = "can make changes to the document"),
  #        # Brief explanatory text to show in the sharing UI indicating
  #        # what a user assigned this role will be able to do with the grain.
  #
  #        description = (defaultText = "editors may view all site data and change settings."),
  #        # Prose describing what this role means, suitable for a tool tip or similar help text.
  #      ),
  #      (
  #        title = (defaultText = "viewer"),
  #        permissions  = [false],
  #        verbPhrase = (defaultText = "can view the document"),
  #        description = (defaultText = "viewers may view what other users have written."),
  #      ),
  #    ],
  #  ),
  #  #apiPath = "/api",
  #  # Apps can export an API to the world.  The API is to be used primarily by Javascript
  #  # code and native apps, so it can't serve out regular HTML to browsers.  If a request
  #  # comes in to your app's API, sandstorm-http-bridge will prefix the request's path with
  #  # this string, if specified.
  #),
);

const myCommand :Spk.Manifest.Command = (
  # Here we define the command used to start up your server.
  argv = ["/sandstorm-http-bridge", "8000", "--", "/bin/bash", "/opt/app/.sandstorm/launcher.sh"],
  environ = [
    # Note that this defines the *entire* environment seen by your app.
    (key = "PATH", value = "/usr/local/bin:/usr/bin:/bin"),
    (key = "SANDSTORM", value = "1"),
    # Export SANDSTORM=1 into the environment, so that apps running within Sandstorm
    # can detect if $SANDSTORM="1" at runtime, switching UI and/or backend to use
    # the app's Sandstorm-specific integration code.
  ]
);
