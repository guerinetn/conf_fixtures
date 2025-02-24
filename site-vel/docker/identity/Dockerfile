ARG IMAGE_KEYCLOAK=quay.io/keycloak/keycloak:24.0.5
ARG IMAGE_UBI=registry.access.redhat.com/ubi9:9.5
FROM ${IMAGE_KEYCLOAK} AS builder

# Enable health and metrics support
ENV KC_HEALTH_ENABLED=true
ENV KC_METRICS_ENABLED=true
COPY ./docker/identity/create_service_account.sh /opt/keycloak/data/import/mar_users.json
COPY ./docker/identity/create_service_account.sh /opt/keycloak/scripts/

WORKDIR /opt/keycloak
RUN /opt/keycloak/bin/kc.sh build

FROM ${IMAGE_UBI} AS ubi-micro-build
# https://www.keycloak.org/server/containers#_installing_additional_rpm_packages

RUN mkdir -p /mnt/rootfs
RUN dnf install --installroot /mnt/rootfs curl --releasever 9 --setopt install_weak_deps=false --nodocs -y && \
    dnf --installroot /mnt/rootfs clean all && \
    rpm --root /mnt/rootfs -e --nodeps setup
WORKDIR /opt/keycloak

FROM ${IMAGE_KEYCLOAK}
COPY --from=builder /opt/keycloak/ /opt/keycloak/
COPY --from=ubi-micro-build /mnt/rootfs /

ENTRYPOINT ["/opt/keycloak/bin/kc.sh"]
