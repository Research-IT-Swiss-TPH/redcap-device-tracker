---
layout: default
title: Home
nav_order: 1
description: "Device Tracker enables sustainable and easy-to-use cross-project multi-device tracking within REDCap."
permalink: /
---

# Track Devices with REDCap 
{: .fs-9 }

Device Tracker enables sustainable and easy-to-use cross-project multi-device tracking within REDCap.
{: .fs-6 .fw-300 }

[Get started now](#getting-started){: .btn .btn-primary .fs-5 .mb-4 .mb-md-0 .mr-2 }
[View it on GitHub](https://github.com/tertek/redcap-device-tracker){: .btn .fs-5 .mb-4 .mb-md-0 }

---


{: .warning }
> The current release of the module is still in  `beta` state. See [the ROADMAP]({{ site.baseurl }}{% link CHANGELOG.md %}) for a list of releases, new features, and bug fixes. 

Device Tracker is a [REDCap External Module](https://redcap.vanderbilt.edu/consortium/modules/) for tracking devices (or any other physical entities with unique IDs and suitable life cycles) within a cross-project context. Device Tracker manages a project as device data storage where requests from tracking projects are being processed. The device tracking is constructed upon a fixed Device Lifecycle where Tracking Actions manipulate the device state. All tracking actions are being logged and can be monitored. 


The whole module has been built with the design philosophy that device states should not be manipulated through the device data storing project itself, but only through tracking requests.


