{
    # Pinning packages with URLs inside a Nix expression
    # https://nix.dev/tutorials/first-steps/towards-reproducibility-pinning-nixpkgs#pinning-packages-with-urls-inside-a-nix-expression
    # Picking the commit can be done via https://status.nixos.org,
    # which lists all the releases and the latest commit that has passed all tests.
    pkgs ? import (fetchTarball "https://github.com/NixOS/nixpkgs/archive/c002c6aa977ad22c60398daaa9be52f2203d0006.tar.gz") {},
    php ? pkgs.php83.buildEnv {
      extensions = ({ enabled, all }: enabled ++ (with all; [
          openssl
          pcntl
          pdo_mysql
          mbstring
          intl
          curl
          bcmath
          apcu
          xdebug
      ]));
      extraConfig = ''
        xdebug.mode=develop,debug
        memory_limit=256M
      '';
    },
}:

pkgs.mkShell {
    buildInputs = [
        php
        pkgs.git
        pkgs.openssh
        pkgs.just
        pkgs.unzip
    ];
    
    shellHook = ''
        ./install-composer.sh
        git --version
        php -v
        ./composer --version
        symfony -V
    '';
}