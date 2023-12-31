<!--
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */
 -->

<template>
  <oxd-dialog class="orangehrm-dialog-modal" @update:show="onCancel">
    <div class="orangehrm-modal-header">
      <oxd-text type="card-title">{{ $t('admin.edit_subscriber') }}</oxd-text>
    </div>
    <oxd-divider />
    <oxd-form :loading="isLoading" @submit-valid="onSave">
      <oxd-form-row>
        <oxd-input-field
          v-model="subscriber.name"
          :label="$t('general.name')"
          :rules="rules.name"
          required
        />
      </oxd-form-row>
      <oxd-form-row>
        <oxd-input-field
          v-model="subscriber.email"
          :label="$t('general.email')"
          :rules="rules.email"
          required
        />
      </oxd-form-row>
      <oxd-divider />

      <oxd-form-actions>
        <required-text />
        <oxd-button
          type="button"
          display-type="ghost"
          :label="$t('general.cancel')"
          @click="onCancel"
        />
        <submit-button />
      </oxd-form-actions>
    </oxd-form>
  </oxd-dialog>
</template>

<script>
import {APIService} from '@/core/util/services/api.service';
import {
  required,
  validEmailFormat,
  shouldNotExceedCharLength,
} from '@ohrm/core/util/validation/rules';
import {OxdDialog} from '@ohrm/oxd';

const subscriberModel = {
  name: '',
  email: '',
};

export default {
  name: 'EditSubscriber',
  components: {
    'oxd-dialog': OxdDialog,
  },
  props: {
    data: {
      type: Object,
      default: () => ({}),
    },
  },
  emits: ['close'],
  setup(props) {
    const http = new APIService(
      window.appGlobal.baseUrl,
      `/api/v2/admin/email-subscriptions/${props.data.subscriptionId}/subscribers`,
    );
    return {
      http,
    };
  },
  data() {
    return {
      isLoading: false,
      subscriber: {...subscriberModel},
      rules: {
        name: [required, shouldNotExceedCharLength(100)],
        email: [required, validEmailFormat, shouldNotExceedCharLength(100)],
      },
    };
  },
  beforeMount() {
    this.isLoading = true;
    this.http
      .get(this.data.id)
      .then((response) => {
        const {data} = response.data;
        this.subscriber.name = data.name;
        this.subscriber.email = data.email;
        // Fetch list data for unique test
        return this.http.getAll();
      })
      .then((response) => {
        const {data} = response.data;
        if (data) {
          this.rules.email.push((v) => {
            const index = data.findIndex((item) => item.email == v);
            if (index > -1) {
              const {id} = data[index];
              return id != this.data.id
                ? this.$t('general.already_exists')
                : true;
            } else {
              return true;
            }
          });
        }
      })
      .finally(() => {
        this.isLoading = false;
      });
  },
  methods: {
    onSave() {
      this.isLoading = true;
      this.http
        .update(this.data.id, {
          ...this.subscriber,
        })
        .then(() => {
          return this.$toast.updateSuccess();
        })
        .then(() => {
          this.onCancel();
        });
    },
    onCancel() {
      this.subscriber = {...subscriberModel};
      this.$emit('close', true);
    },
  },
};
</script>

<style scoped>
.level-label {
  font-size: 0.75rem;
}
</style>
